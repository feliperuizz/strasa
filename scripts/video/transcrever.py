"""Transcrição com tempo por palavra (faster-whisper, CPU int8).

Chamado pelo Estúdio de vídeo (App\\Video\\Transcricao, driver "faster_whisper"):

    python3 -I transcrever.py audio.wav saida.json --modelo small --idioma pt --contexto "Barbearia do Zé"

Instalação no servidor (uma vez, ~300 MB + o modelo baixado no primeiro uso):

    python3 -m pip install --user faster-whisper

Modelos: tiny/base (rápidos, erram mais), small (bom equilíbrio em CPU),
medium (melhor, ~2x mais lento e ~1,5 GB de RAM). Só biblioteca padrão
além do faster-whisper.
"""

import argparse
import json
import os
import sys


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("audio")
    p.add_argument("saida")
    p.add_argument("--modelo", default="small")
    p.add_argument("--idioma", default="pt")
    p.add_argument("--contexto", default="")
    p.add_argument("--threads", type=int, default=2)
    a = p.parse_args()

    try:
        from faster_whisper import WhisperModel
    except ImportError:
        print("faster-whisper não instalado: python3 -m pip install --user faster-whisper", file=sys.stderr)
        return 2

    modelo = WhisperModel(a.modelo, device="cpu", compute_type="int8", cpu_threads=max(1, a.threads))
    segmentos, info = modelo.transcribe(
        a.audio,
        language=a.idioma or None,
        word_timestamps=True,
        vad_filter=True,
        vad_parameters={"min_silence_duration_ms": 300},
        initial_prompt=a.contexto or None,
        # Mantém hesitações e recomeços: a etapa de cortes precisa vê-los.
        condition_on_previous_text=False,
    )

    palavras = []
    for seg in segmentos:
        for w in seg.words or []:
            palavras.append({"w": w.word.strip(), "i": round(w.start, 3), "f": round(w.end, 3), "p": round(w.probability, 2)})

    tmp = a.saida + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump({"idioma": info.language, "palavras": palavras}, f, ensure_ascii=False)
    os.replace(tmp, a.saida)
    return 0


if __name__ == "__main__":
    sys.exit(main())
