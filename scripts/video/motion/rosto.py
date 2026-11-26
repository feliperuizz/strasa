#!/usr/bin/env python3
"""
Onde está o rosto em cada instante de um vídeo (YuNet, OpenCV), para:
  - o Claude posicionar as animações sem cobrir o rosto;
  - a câmera centralizar o rosto (foco) quando o plano não disser;
  - as folhas de revisão marcarem o rosto.

Uso:  python -I rosto.py proxy.mp4 saida.json modelo_yunet.onnx [--fps 2] [--threads 2]
Saída: {"largura", "altura", "quadros": [{"t": 0.5, "r": [x, y, w, h]} ...]}  (0–1; r=null sem rosto)
Roda no proxy (540 px): rápido e leve, um processo, threads limitadas.
"""
import json
import os
import sys

args = sys.argv[1:]
video, saida, modelo = args[0], args[1], args[2]
fps = float(args[args.index("--fps") + 1]) if "--fps" in args else 2.0
threads = int(args[args.index("--threads") + 1]) if "--threads" in args else 2
os.environ["OMP_NUM_THREADS"] = str(threads)

import cv2  # noqa: E402

cv2.setNumThreads(threads)
cap = cv2.VideoCapture(video)
if not cap.isOpened():
    sys.exit("não abriu o vídeo")
W = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
H = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
origem_fps = cap.get(cv2.CAP_PROP_FPS) or 30.0
total = int(cap.get(cv2.CAP_PROP_FRAME_COUNT) or 0)
passo = max(1, int(round(origem_fps / fps)))
det = cv2.FaceDetectorYN.create(modelo, "", (W, H), 0.75, 0.3, 50)

quadros = []
n = 0
while True:
    ok = cap.grab()
    if not ok:
        break
    if n % passo == 0:
        ok, img = cap.retrieve()
        if not ok:
            break
        _, faces = det.detect(img)
        r = None
        if faces is not None and len(faces):
            f = max(faces, key=lambda f: f[2] * f[3])  # o maior rosto
            x, y, w, h = [float(v) for v in f[:4]]
            r = [round(max(0, x) / W, 4), round(max(0, y) / H, 4), round(w / W, 4), round(h / H, 4)]
        quadros.append({"t": round(n / origem_fps, 2), "r": r})
    n += 1

json.dump({"largura": W, "altura": H, "quadros": quadros}, open(saida, "w"))
print(json.dumps({"quadros": len(quadros), "com_rosto": sum(1 for q in quadros if q["r"])}))
