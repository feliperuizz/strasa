#!/usr/bin/env python3
"""
Motor de motion do Estúdio de vídeo: desenha os componentes animados
(janela de terminal, contador, cards, apps, comentário, passo a passo…)
quadro a quadro com Skia e entrega um vídeo com transparência (QuickTime
RLE, ARGB) para o FFmpeg sobrepor na montagem.

Por que não HTML/Chrome (HyperFrames, Remotion): a hospedagem compartilhada
limita threads e processos por conta, e o Chrome abre dezenas. Aqui é um
processo só, com threads limitadas, e o mesmo motor gráfico do Chrome (Skia).

Uso:  python -I render.py pedido.json
  pedido.json: largura, altura (formato final), escala, fps, duracao,
  identidade, fontes, icones, logo, elementos[], saida, ffmpeg, threads.
  Depuração: "quadros": [t, ...] + "pasta_quadros" (+ "fundo": imagem)
  salva PNGs desses instantes em vez do vídeo.
"""
import json
import os
import subprocess
import sys

AQUI = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, AQUI)  # -I tira a pasta do script do sys.path

pedido = json.load(open(sys.argv[1], encoding="utf-8"))
T = str(max(1, int(pedido.get("threads") or 2)))
for var in ("OMP_NUM_THREADS", "OPENBLAS_NUM_THREADS", "MKL_NUM_THREADS", "NUMEXPR_NUM_THREADS"):
    os.environ[var] = T

import numpy as np  # noqa: E402
import skia  # noqa: E402

from base import Cena, imagem  # noqa: E402
from componentes import COMPONENTES  # noqa: E402

FPS = int(pedido.get("fps") or 30)
escala = float(pedido.get("escala") or 1.0)
LW = int(round(pedido["largura"] * escala / 2) * 2)
LH = int(round(pedido["altura"] * escala / 2) * 2)
cena = Cena(pedido)
k = LW / float(cena.W)

elementos = []
avisos = []
for d in pedido.get("elementos", []):
    cls = COMPONENTES.get(d.get("tipo"))
    if not cls:
        continue
    try:
        elementos.append(cls(d, cena))
    except Exception as e:  # um componente com defeito não derruba o vídeo
        avisos.append("%s: %s" % (d.get("id", d.get("tipo")), e))

surface = skia.Surface(LW, LH)
canvas = surface.getCanvas()
# Alfa PRÉ-multiplicado: a leitura vira cópia direta (a conversão para "reto"
# custava tanto quanto desenhar). A montagem usa overlay=...:alpha=premultiplied.
INFO = skia.ImageInfo.Make(LW, LH, skia.kRGBA_8888_ColorType, skia.kPremul_AlphaType)
buf = np.zeros((LH, LW, 4), dtype=np.uint8)

fundo = None
if pedido.get("fundo") and os.path.isfile(pedido["fundo"]):
    fundo = skia.Image.open(pedido["fundo"])


def desenhar(t):
    canvas.clear(skia.ColorTRANSPARENT)
    if fundo is not None:
        s = max(LW / fundo.width(), LH / fundo.height())
        w, h = fundo.width() * s, fundo.height() * s
        imagem(canvas, fundo, (LW - w) / 2, (LH - h) / 2, w, h)
    canvas.save()
    canvas.scale(k, k)
    for el in elementos:
        if el.ativo(t):
            try:
                el.quadro(canvas, t)
            except Exception as e:
                msg = "%s: %s" % (el.d.get("id", el.d.get("tipo")), e)
                if msg not in avisos:
                    avisos.append(msg)
    canvas.restore()


if pedido.get("quadros"):
    os.makedirs(pedido["pasta_quadros"], exist_ok=True)
    for t in pedido["quadros"]:
        desenhar(float(t))
        surface.makeImageSnapshot().save(os.path.join(pedido["pasta_quadros"], "q_%06.2f.png" % float(t)), skia.kPNG)
    print(json.dumps({"avisos": avisos}))
    sys.exit(0)

total = int(round(float(pedido["duracao"]) * FPS))
cmd = [
    pedido.get("ffmpeg") or "ffmpeg", "-hide_banner", "-loglevel", "error", "-nostdin", "-y",
    "-filter_threads", T,
    "-f", "rawvideo", "-pix_fmt", "rgba", "-s", "%dx%d" % (LW, LH), "-r", str(FPS), "-i", "-",
    "-c:v", "qtrle", "-pix_fmt", "argb", "-threads", T, pedido["saida"],
]
ff = subprocess.Popen(cmd, stdin=subprocess.PIPE, stderr=subprocess.PIPE)
vazio = bytes(LW * LH * 4)
try:
    for f in range(total):
        t = f / float(FPS)
        if not any(el.ativo(t) for el in elementos):
            ff.stdin.write(vazio)
            continue
        desenhar(t)
        surface.readPixels(INFO, buf)
        ff.stdin.write(buf.tobytes())
        if f % 30 == 0:
            print("progresso %.3f" % (f / float(total)), flush=True)
    ff.stdin.close()
except BrokenPipeError:
    pass
erro = ff.stderr.read().decode("utf-8", "replace")
if ff.wait() != 0:
    sys.exit("ffmpeg falhou ao gravar o motion: " + erro[-800:])
print(json.dumps({"avisos": avisos}))
