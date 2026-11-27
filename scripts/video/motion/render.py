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


def folhas(lista):
    """
    Revisão visual para o Claude: cada folha = alguns instantes de uma animação
    (entrada, auge, saída) sobre o quadro real do vídeo, lado a lado, com as
    zonas proibidas dos botões do app (vermelho) e o rosto (verde) marcados.
    """
    fonte = cena.fontes.fonte("forte", 30)
    for folha in lista:
        qs = folha.get("quadros") or []
        if not qs:
            continue
        cw, ch = LW, LH
        gap, rod = 12, 64
        s = skia.Surface(len(qs) * cw + (len(qs) - 1) * gap, ch + rod)
        cv = s.getCanvas()
        cv.clear(skia.Color(18, 18, 20))
        for i, q in enumerate(qs):
            t = float(q["t"])
            img = skia.Image.open(q["fundo"]) if q.get("fundo") and os.path.isfile(q["fundo"]) else None
            canvas.clear(skia.Color(30, 30, 34))
            if img is not None:
                sc = max(LW / img.width(), LH / img.height())
                imagem(canvas, img, (LW - img.width() * sc) / 2, (LH - img.height() * sc) / 2, img.width() * sc, img.height() * sc)
            canvas.save()
            canvas.scale(k, k)
            for el in elementos:
                if el.ativo(t):
                    try:
                        el.quadro(canvas, t)
                    except Exception as e:
                        avisos.append("%s: %s" % (el.d.get("id", el.d.get("tipo")), e))
            canvas.restore()
            # Guias: zonas que o app cobre (8% topo, 20% base, 12% direita; só no vertical) e o rosto.
            if pedido.get("zonas", True):
                vermelho = skia.Paint(Color=skia.Color(255, 40, 40, 46))
                canvas.drawRect(skia.Rect.MakeXYWH(0, 0, LW, LH * 0.08), vermelho)
                canvas.drawRect(skia.Rect.MakeXYWH(0, LH * 0.80, LW, LH * 0.20), vermelho)
                canvas.drawRect(skia.Rect.MakeXYWH(LW * 0.88, LH * 0.08, LW * 0.12, LH * 0.72), vermelho)
            if q.get("rosto"):
                rx, ry, rw, rh = q["rosto"]
                verde = skia.Paint(AntiAlias=True, Color=skia.Color(60, 230, 120, 220), Style=skia.Paint.kStroke_Style, StrokeWidth=3)
                canvas.drawRect(skia.Rect.MakeXYWH(rx * LW, ry * LH, rw * LW, rh * LH), verde)
            snap = surface.makeImageSnapshot()
            x = i * (cw + gap)
            cv.drawImage(snap, x, 0)
            rotulo = "%s  %s  %.1fs" % (folha.get("rotulo", ""), q.get("nome", ""), t)
            cv.drawString(rotulo, x + 10, ch + 42, fonte, skia.Paint(AntiAlias=True, Color=skia.Color(235, 235, 240)))
        s.makeImageSnapshot().save(folha["saida"], skia.kPNG)


if pedido.get("folhas"):
    folhas(pedido["folhas"])
    print(json.dumps({"avisos": avisos}))
    sys.exit(0)

if pedido.get("quadros"):
    os.makedirs(pedido["pasta_quadros"], exist_ok=True)
    for t in pedido["quadros"]:
        desenhar(float(t))
        surface.makeImageSnapshot().save(os.path.join(pedido["pasta_quadros"], "q_%06.2f.png" % float(t)), skia.kPNG)
    print(json.dumps({"avisos": avisos}))
    sys.exit(0)

# Pedaços: só os trechos com animação viram vídeo (um vlog de 15 min com 30
# animações não desenha 27 mil quadros vazios). Elementos a menos de 0,5 s um
# do outro ficam no mesmo pedaço. A montagem sobrepõe cada um no seu tempo.
intervalos = sorted((max(0.0, el.inicio), min(float(pedido["duracao"]), el.fim)) for el in elementos)
pedacos = []
for a, b in intervalos:
    if b <= a:
        continue
    if pedacos and a - pedacos[-1][1] < 0.5:
        pedacos[-1][1] = max(pedacos[-1][1], b)
    else:
        pedacos.append([a, b])

base = pedido["saida"]
raiz, ext = os.path.splitext(base)
quadros_total = sum(int(round((b - a) * FPS)) + 1 for a, b in pedacos) or 1
feitos = 0
saida_pedacos = []
for np_, (a, b) in enumerate(pedacos):
    f0 = int(a * FPS)
    f1 = int(round(b * FPS)) + 1
    arquivo = "%s_p%02d%s" % (raiz, np_, ext)
    cmd = [
        pedido.get("ffmpeg") or "ffmpeg", "-hide_banner", "-loglevel", "error", "-nostdin", "-y",
        "-filter_threads", T,
        "-f", "rawvideo", "-pix_fmt", "rgba", "-s", "%dx%d" % (LW, LH), "-r", str(FPS), "-i", "-",
        "-c:v", "qtrle", "-pix_fmt", "argb", "-threads", T, arquivo,
    ]
    ff = subprocess.Popen(cmd, stdin=subprocess.PIPE, stderr=subprocess.PIPE)
    try:
        for f in range(f0, f1):
            desenhar(f / float(FPS))
            surface.readPixels(INFO, buf)
            ff.stdin.write(buf.tobytes())
            feitos += 1
            if feitos % 30 == 0:
                print("progresso %.3f" % (feitos / float(quadros_total)), flush=True)
        ff.stdin.close()
    except BrokenPipeError:
        pass
    erro = ff.stderr.read().decode("utf-8", "replace")
    if ff.wait() != 0:
        sys.exit("ffmpeg falhou ao gravar o motion: " + erro[-800:])
    saida_pedacos.append({"arquivo": arquivo, "inicio": round(f0 / float(FPS), 4)})

print(json.dumps({"avisos": avisos, "pedacos": saida_pedacos}))
