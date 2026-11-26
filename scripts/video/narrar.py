#!/usr/bin/env python3
"""
Narração do Estúdio de vídeo: texto -> voz (WAV), 100% local e gratuito.

Motores:
  kokoro      Kokoro-82M (ONNX, Apache-2.0). Natural, leve (~90-330 MB) e
              rápido no processador. Vozes pt-BR: pf_dora, pm_alex, pm_santa.
  chatterbox  Chatterbox Multilingual (Resemble AI, MIT). Mais humano e clona
              uma voz a partir de ~10 s de gravação, mas é pesado (torch +
              ~3 GB de modelo) e lento no processador. Ambiente Python próprio.

Uso (chamado pelo PHP, App\\Video\\Narracao):
  python -I narrar.py pedido.json resultado.json

pedido.json:
  {"motor": "kokoro", "voz": "pf_dora", "velocidade": 1.0, "modelos": "/pasta/dos/modelos",
   "referencia": null, "threads": 2,
   "falas": [{"id": "n01", "texto": "...", "saida": "/caminho/n01.wav"}]}

resultado.json:
  {"falas": {"n01": {"duracao": 3.42}}}

Servidor compartilhado: limita threads (OMP/ORT/torch) — a hospedagem mata
processo que abre thread demais ("failed to spawn thread").
"""
import json
import os
import re
import sys
import wave

pedido = json.load(open(sys.argv[1], encoding="utf-8"))
T = str(max(1, int(pedido.get("threads") or 2)))
for var in ("OMP_NUM_THREADS", "MKL_NUM_THREADS", "OPENBLAS_NUM_THREADS", "NUMEXPR_NUM_THREADS",
            "RAYON_NUM_THREADS", "ORT_NUM_THREADS", "VIDEO_THREADS"):
    os.environ[var] = T
os.environ["TOKENIZERS_PARALLELISM"] = "false"
os.environ.setdefault("HF_HUB_DISABLE_TELEMETRY", "1")

import numpy as np  # noqa: E402

MODELOS = pedido["modelos"]
VELOCIDADE = max(0.7, min(1.4, float(pedido.get("velocidade") or 1.0)))


def salvar(caminho, audio, sr):
    """WAV mono 16 bits, sem silêncio nas pontas (folga de 40 ms)."""
    audio = np.asarray(audio, dtype=np.float32).flatten()
    if audio.size:
        limiar = max(1e-3, float(np.max(np.abs(audio))) * 0.02)
        voz = np.where(np.abs(audio) > limiar)[0]
        if voz.size:
            folga = int(sr * 0.04)
            audio = audio[max(0, voz[0] - folga): min(audio.size, voz[-1] + folga)]
    pcm = (np.clip(audio, -1.0, 1.0) * 32767).astype(np.int16)
    os.makedirs(os.path.dirname(caminho), exist_ok=True)
    with wave.open(caminho, "wb") as w:
        w.setnchannels(1)
        w.setsampwidth(2)
        w.setframerate(sr)
        w.writeframes(pcm.tobytes())
    return round(pcm.size / sr, 3)


def frases(texto):
    """Quebra em frases curtas (o Chatterbox perde o fôlego em texto longo)."""
    partes = re.split(r"(?<=[.!?…])\s+", texto.strip())
    saida, atual = [], ""
    for p in partes:
        if len(atual) + len(p) < 220:
            atual = (atual + " " + p).strip()
        else:
            if atual:
                saida.append(atual)
            atual = p
    if atual:
        saida.append(atual)
    return saida or [texto]


def kokoro():
    import onnxruntime as ort
    from kokoro_onnx import Kokoro

    modelo = next((os.path.join(MODELOS, m) for m in ("kokoro-v1.0.onnx", "kokoro-v1.0.int8.onnx")
                   if os.path.isfile(os.path.join(MODELOS, m))), None)
    if not modelo:
        sys.exit("Modelo do Kokoro não encontrado em " + MODELOS)

    opcoes = ort.SessionOptions()
    opcoes.intra_op_num_threads = int(T)
    opcoes.inter_op_num_threads = 1
    sessao = ort.InferenceSession(modelo, opcoes, providers=["CPUExecutionProvider"])
    k = Kokoro.from_session(sessao, os.path.join(MODELOS, "voices-v1.0.bin"))

    def falar(texto):
        return k.create(texto, voice=pedido["voz"], speed=VELOCIDADE, lang="pt-br")

    return falar


def chatterbox():
    import torch
    torch.set_num_threads(int(T))
    torch.set_num_interop_threads(1)
    from chatterbox.mtl_tts import ChatterboxMultilingualTTS

    m = ChatterboxMultilingualTTS.from_pretrained(device="cpu")
    ref = pedido.get("referencia")
    extra = {"audio_prompt_path": ref} if ref and os.path.isfile(ref) else {}
    pausa = np.zeros(int(m.sr * 0.18), dtype=np.float32)

    def falar(texto):
        pedacos = []
        for f in frases(texto):
            wav = m.generate(f, language_id="pt", exaggeration=0.5, cfg_weight=0.5, **extra)
            pedacos += [wav.squeeze(0).cpu().numpy().astype(np.float32), pausa]
        # A velocidade do Chatterbox é ajustada depois, no FFmpeg (atempo).
        return np.concatenate(pedacos[:-1]) if pedacos else np.zeros(1, np.float32), m.sr

    return falar


falar = {"kokoro": kokoro, "chatterbox": chatterbox}[pedido["motor"]]()
resultado = {"falas": {}}

for f in pedido["falas"]:
    audio, sr = falar(f["texto"])
    resultado["falas"][f["id"]] = {"duracao": salvar(f["saida"], audio, sr)}
    print(f"{f['id']}: {resultado['falas'][f['id']]['duracao']} s", flush=True)

json.dump(resultado, open(sys.argv[2], "w", encoding="utf-8"))
