#!/usr/bin/env bash
# =============================================================================
# Estúdio de vídeo do STRASA — preparação do servidor (VPS com cPanel)
#
# Rode UMA vez, por SSH, logado como o usuário do cPanel (não precisa de root):
#
#     cd ~/public_html/strasa && bash scripts/video/instalar-servidor.sh
#
# Opções:  --sem-whisper     não instala a transcrição local (sem legenda automática)
#          --sem-voz         não instala a narração (voz gerada)
#          --com-chatterbox  instala também a voz "Humana+" (mais natural, porém
#                            pesada: ~4 GB de disco e bem mais lenta no processador)
#
# O motor de edição é o Claude (CLI) com a assinatura da agência. A legenda
# precisa de transcrição, que o Claude não faz (ele não ouve áudio): ela roda
# local, de graça, com o faster-whisper — nenhuma outra assinatura.
#
# O que faz (tudo dentro da sua home, nada no sistema):
#   1. FFmpeg estático completo (libass, zscale, x264) em ~/strasa-video/bin
#   2. Claude Code (instalador nativo, sem Node) em ~/.local/bin/claude
#   3. Pasta de dados do estúdio fora do document root: ~/strasa-video/dados
#   4. Confere a saída HTTPS (porta 443) para a Anthropic — nenhuma porta de
#      ENTRADA é aberta; o login do Claude é por token (claude setup-token)
#   5. Fontes + efeitos sonoros (php artisan video:instalar) e o diagnóstico
#   6. Mostra as linhas para colar no .env
#
# É idempotente: rodar de novo só completa o que falta.
# =============================================================================
set -euo pipefail

COM_WHISPER=1
COM_CLAUDE=1
COM_VOZ=1
COM_CHATTERBOX=0
for arg in "$@"; do
    case "$arg" in
        --sem-whisper) COM_WHISPER=0 ;;
        --sem-voz) COM_VOZ=0 ;;
        --com-chatterbox) COM_CHATTERBOX=1 ;;
        *) echo "Opção desconhecida: $arg"; exit 1 ;;
    esac
done

APP="$(cd "$(dirname "$0")/../.." && pwd)"
BASE="$HOME/strasa-video"
BIN="$BASE/bin"
DADOS="$BASE/dados"
PHP="${PHP:-php}"
mkdir -p "$BIN" "$DADOS"
# /tmp da hospedagem é noexec (biblioteca .so não carrega de lá): temporário na home.
mkdir -p "$BASE/tmp" && export TMPDIR="$BASE/tmp"

verde() { printf '\033[32m%s\033[0m\n' "$*"; }
amarelo() { printf '\033[33m%s\033[0m\n' "$*"; }
vermelho() { printf '\033[31m%s\033[0m\n' "$*"; }
passo() { printf '\n\033[1m== %s\033[0m\n' "$*"; }

passo "Sistema"
echo "Usuário: $(whoami)   Home: $HOME   App: $APP"
echo "Arquitetura: $(uname -m)   Núcleos: $(nproc 2>/dev/null || echo '?')   $(ldd --version 2>/dev/null | head -1)"
if [ -r /proc/meminfo ]; then
    awk '/MemTotal|MemAvailable/ {printf "%s %.1f GB\n", $1, $2/1048576}' /proc/meminfo
fi
df -h "$HOME" | tail -1 | awk '{print "Disco na home: " $4 " livres de " $2}'

# --- 1. FFmpeg ---------------------------------------------------------------
passo "0. Python (para descompactar e para as legendas)"
PYTHON=""
UV=""
for py in python3.12 python3.11 python3.10 python3.9 python3; do
    if command -v "$py" >/dev/null 2>&1 && "$py" -c 'import sys, lzma; sys.exit(0 if sys.version_info >= (3, 9) else 1)' 2>/dev/null; then PYTHON="$(command -v "$py")"; break; fi
done
if [ -z "$PYTHON" ]; then
    # Servidor sem Python (ou antigo): baixa o uv (Astral, .tar.gz) e um Python 3.11 próprio, tudo na home.
    if [ ! -x "$BIN/uv" ]; then
        echo "Sem Python 3.9+ no servidor: instalando um Python próprio na sua home (uv + Python 3.11, ~60 MB)…"
        curl -fsSL https://github.com/astral-sh/uv/releases/latest/download/uv-x86_64-unknown-linux-gnu.tar.gz | tar -xz -C "$BIN" --strip-components=1
    fi
    UV="$BIN/uv"
    "$UV" python install --quiet 3.11
    PYTHON="$("$UV" python find 3.11)"
fi
verde "Python: $("$PYTHON" --version 2>&1) em $PYTHON"
PYTHON_BASE="$PYTHON"

passo "1. FFmpeg"
if [ -x "$BIN/ffmpeg" ] && "$BIN/ffmpeg" -hide_banner -filters 2>/dev/null | grep -q ' ass '; then
    verde "Já instalado: $("$BIN/ffmpeg" -version | head -1)"
else
    case "$(uname -m)" in
        x86_64) ARQ=amd64 ;;
        aarch64|arm64) ARQ=arm64 ;;
        *) vermelho "Arquitetura $(uname -m) sem build estático conhecido. Instale o FFmpeg manualmente."; exit 1 ;;
    esac
    TMP="$(mktemp -d)"
    echo "Baixando o FFmpeg estático ($ARQ, ~80 MB) de johnvansickle.com…"
    curl -fL --retry 3 -o "$TMP/ffmpeg.tar.xz" "https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-$ARQ-static.tar.xz"
    # Hospedagem sem o programa xz: o Python descompacta (módulo lzma, padrão).
    if command -v xz >/dev/null 2>&1; then
        tar -xJf "$TMP/ffmpeg.tar.xz" -C "$TMP"
    else
        echo "Sem o xz no servidor: descompactando com o Python…"
        "$PYTHON" -c 'import sys, tarfile; tarfile.open(sys.argv[1]).extractall(sys.argv[2])' "$TMP/ffmpeg.tar.xz" "$TMP"
    fi
    cp "$TMP"/ffmpeg-*-static/ffmpeg "$TMP"/ffmpeg-*-static/ffprobe "$BIN/"
    chmod +x "$BIN/ffmpeg" "$BIN/ffprobe"
    rm -rf "$TMP"
    verde "Instalado: $("$BIN/ffmpeg" -version | head -1)"
fi
for f in ass zoompan xfade loudnorm zscale sidechaincompress; do
    if "$BIN/ffmpeg" -hide_banner -filters 2>/dev/null | grep -q " $f "; then echo "  ✓ filtro $f"; else amarelo "  ! falta o filtro $f"; fi
done

# --- 2. Claude Code ----------------------------------------------------------
passo "2. Claude Code"
CLAUDE_BIN=""
if [ "$COM_CLAUDE" = 1 ]; then
    if command -v claude >/dev/null 2>&1; then CLAUDE_BIN="$(command -v claude)";
    elif [ -x "$HOME/.local/bin/claude" ]; then CLAUDE_BIN="$HOME/.local/bin/claude"; fi

    if [ -z "$CLAUDE_BIN" ]; then
        echo "Instalando pelo instalador oficial (claude.ai/install.sh)…"
        curl -fsSL https://claude.ai/install.sh | bash
        CLAUDE_BIN="$HOME/.local/bin/claude"
    fi

    if [ -x "$CLAUDE_BIN" ]; then
        verde "Claude Code: $("$CLAUDE_BIN" --version 2>/dev/null || echo '?') em $CLAUDE_BIN"
    else
        vermelho "Não consegui instalar o Claude Code. Veja https://docs.claude.com/en/docs/claude-code/setup"
    fi
else
    amarelo "Pulado (--sem-claude). As edições vão usar o piloto automático."
fi

# --- 3. Transcrição (opcional) -----------------------------------------------
passo "3. Transcrição local (legendas)"
TRANSCRICAO=faster_whisper
if [ "$COM_WHISPER" = 0 ]; then
    TRANSCRICAO=nenhum; amarelo "Pulado (--sem-whisper): vídeos sairão sem legenda automática."
else
    # Ambiente isolado na home: não depende de pip/permissões do sistema.
    VENV="$BASE/venv"
    [ -z "$UV" ] && [ -x "$BIN/uv" ] && UV="$BIN/uv"
    if [ ! -x "$VENV/bin/python" ]; then
        if [ -n "$UV" ]; then "$UV" venv --quiet --python "$PYTHON" "$VENV"; else "$PYTHON" -m venv "$VENV"; fi
    fi
    if [ -n "$UV" ]; then
        "$UV" pip install --quiet --python "$VENV/bin/python" faster-whisper && OK=1 || OK=0
    else
        # Python do uv cria venv sem pip: garante o pip antes.
        "$VENV/bin/python" -m pip --version >/dev/null 2>&1 || "$VENV/bin/python" -m ensurepip --upgrade >/dev/null 2>&1 || true
        "$VENV/bin/python" -m pip install --quiet --upgrade pip >/dev/null 2>&1 || true
        "$VENV/bin/python" -m pip install --quiet faster-whisper && OK=1 || OK=0
    fi
    if [ "$OK" = 1 ]; then
        PYTHON="$VENV/bin/python"
        verde "faster-whisper instalado em $PYTHON (o modelo 'small', ~500 MB, baixa na primeira legenda)."
    else
        TRANSCRICAO=nenhum; vermelho "Falhou ao instalar o faster-whisper. Veja a mensagem acima."
    fi
fi

# --- 3b. Narração (voz gerada, opcional) -------------------------------------
passo "3b. Narração com voz gerada (Kokoro, local e gratuito)"
VOZES="$BASE/vozes"
NARRACAO_PY=""
CHATTERBOX_PY=""
mkdir -p "$VOZES"
# Python só da voz: o Kokoro pede numpy 2 e não pode quebrar o faster-whisper.
criar_venv() {  # $1 = pasta
    if [ ! -x "$1/bin/python" ]; then
        if [ -n "$UV" ]; then "$UV" venv --quiet --python "$PYTHON_BASE" "$1"; else "$PYTHON_BASE" -m venv "$1"; fi
    fi
    if [ -z "$UV" ]; then
        "$1/bin/python" -m pip --version >/dev/null 2>&1 || "$1/bin/python" -m ensurepip --upgrade >/dev/null 2>&1 || true
    fi
}
instalar_pip() {  # $1 = pasta do venv, resto = pacotes
    local venv="$1"; shift
    if [ -n "$UV" ]; then "$UV" pip install --quiet --python "$venv/bin/python" "$@"; else "$venv/bin/python" -m pip install --quiet "$@"; fi
}
baixar() {  # $1 = url, $2 = destino (só se ainda não existe)
    [ -s "$2" ] && return 0
    echo "  baixando $(basename "$2")…"
    curl -fL --retry 3 --progress-bar -o "$2.parcial" "$1" && mv "$2.parcial" "$2"
}
if [ "$COM_VOZ" = 0 ]; then
    amarelo "Pulado (--sem-voz)."
else
    [ -z "$UV" ] && [ -x "$BIN/uv" ] && UV="$BIN/uv"
    criar_venv "$BASE/venv-voz"
    if instalar_pip "$BASE/venv-voz" kokoro-onnx; then
        K=https://github.com/thewh1teagle/kokoro-onnx/releases/download/model-files-v1.0
        baixar "$K/kokoro-v1.0.onnx" "$VOZES/kokoro-v1.0.onnx" && baixar "$K/voices-v1.0.bin" "$VOZES/voices-v1.0.bin" \
            && NARRACAO_PY="$BASE/venv-voz/bin/python" && verde "Kokoro instalado (vozes Dora, Alex e Santa)." \
            || vermelho "Falhou ao baixar o modelo de voz. Rode o instalador de novo."
    else
        vermelho "Falhou ao instalar o kokoro-onnx. Veja a mensagem acima."
    fi

    if [ "$COM_CHATTERBOX" = 1 ]; then
        echo "Instalando o Chatterbox (torch para CPU + ~3 GB de modelo; demora)…"
        criar_venv "$BASE/venv-chatterbox"
        if instalar_pip "$BASE/venv-chatterbox" --index-strategy unsafe-best-match --extra-index-url https://download.pytorch.org/whl/cpu chatterbox-tts "setuptools<70" 2>/dev/null \
            || instalar_pip "$BASE/venv-chatterbox" --extra-index-url https://download.pytorch.org/whl/cpu chatterbox-tts "setuptools<70"; then
            echo "Baixando o modelo do Chatterbox…"
            HF_HOME="$VOZES/hf" OMP_NUM_THREADS=2 "$BASE/venv-chatterbox/bin/python" -c 'from huggingface_hub import snapshot_download; snapshot_download("ResembleAI/chatterbox")' \
                && CHATTERBOX_PY="$BASE/venv-chatterbox/bin/python" && verde "Chatterbox instalado (voz Humana+)." \
                || vermelho "Falhou ao baixar o modelo do Chatterbox."
        else
            vermelho "Falhou ao instalar o Chatterbox. A narração continua com o Kokoro."
        fi
    elif [ -x "$BASE/venv-chatterbox/bin/python" ]; then
        CHATTERBOX_PY="$BASE/venv-chatterbox/bin/python"
    fi
fi

# --- 3c. Motor de motion (componentes animados) ------------------------------
passo "3c. Motor de motion (Skia) e ícones (Simple Icons + Lucide)"
ICONES="$BASE/icones"
MOTION_PY=""
[ -z "$UV" ] && [ -x "$BIN/uv" ] && UV="$BIN/uv"
criar_venv "$BASE/venv-motion"
# skia-python novo pede glibc 2.28; em servidor mais antigo o uv/pip cai sozinho na 87.x (glibc 2.17).
# OpenCV (detector de rosto YuNet). numpy < 2.4: o OpenCV ainda não abre com o 2.4.
if instalar_pip "$BASE/venv-motion" skia-python "numpy<2.4" opencv-python-headless; then
    if "$BASE/venv-motion/bin/python" -c 'import skia; s = skia.Surface(8, 8); print("skia", skia.__version__)'; then
        MOTION_PY="$BASE/venv-motion/bin/python"
    else
        vermelho "O skia-python instalou mas não abriu (biblioteca do sistema faltando?). Veja a mensagem acima."
    fi
else
    vermelho "Falhou ao instalar o skia-python. Os vídeos saem sem os componentes de motion."
fi
mkdir -p "$ICONES"
if [ ! -s "$ICONES/yunet.onnx" ]; then
    curl -fsSL --retry 3 -o "$ICONES/yunet.onnx" "https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx" \
        && verde "Detector de rosto (YuNet) baixado." || amarelo "Não baixei o detector de rosto (as animações só não desviam do rosto sozinhas)."
fi
if [ ! -s "$ICONES/marcas.json" ] || [ ! -d "$ICONES/lucide" ]; then
    echo "Baixando os ícones (Simple Icons CC0 + Lucide ISC, ~15 MB)…"
    TMPI="$(mktemp -d)"
    SI_URL="$("$PYTHON_BASE" -c 'import json,urllib.request; print(json.load(urllib.request.urlopen("https://registry.npmjs.org/simple-icons/latest"))["dist"]["tarball"])')"
    LU_URL="$("$PYTHON_BASE" -c 'import json,urllib.request; print(json.load(urllib.request.urlopen("https://registry.npmjs.org/lucide-static/latest"))["dist"]["tarball"])')"
    curl -fsSL --retry 3 -o "$TMPI/si.tgz" "$SI_URL" && curl -fsSL --retry 3 -o "$TMPI/lu.tgz" "$LU_URL" \
        && mkdir -p "$TMPI/si" "$TMPI/lu" "$ICONES/marcas" "$ICONES/lucide" \
        && tar -xzf "$TMPI/si.tgz" -C "$TMPI/si" && tar -xzf "$TMPI/lu.tgz" -C "$TMPI/lu" \
        && cp "$TMPI"/si/package/icons/*.svg "$ICONES/marcas/" && cp "$TMPI"/lu/package/icons/*.svg "$ICONES/lucide/" \
        && "$PYTHON_BASE" -c 'import json,sys; d=json.load(open(sys.argv[1],encoding="utf-8")); json.dump([{"slug":x["slug"],"hex":x["hex"],"title":x["title"]} for x in d], open(sys.argv[2],"w",encoding="utf-8"))' "$TMPI/si/package/data/simple-icons.json" "$ICONES/marcas.json" \
        && verde "Ícones: $(ls "$ICONES/marcas" | wc -l) marcas, $(ls "$ICONES/lucide" | wc -l) genéricos." \
        || vermelho "Falhou ao baixar os ícones. Rode o instalador de novo."
    rm -rf "$TMPI"
else
    verde "Ícones já instalados."
fi

# --- 4. Rede -----------------------------------------------------------------
passo "4. Saída para a internet (porta 443)"
for host in api.anthropic.com claude.ai cdn.jsdelivr.net huggingface.co itunes.apple.com en.wikipedia.org; do
    if curl -s -o /dev/null --max-time 8 "https://$host"; then echo "  ✓ $host"; else vermelho "  ✗ $host bloqueado — libere a SAÍDA na porta 443 no firewall (CSF: TCP_OUT)."; fi
done
echo "Nenhuma porta de entrada é necessária."

# --- 5. Estúdio --------------------------------------------------------------
passo "5. Fontes, efeitos sonoros e diagnóstico"
cd "$APP"
export VIDEO_FFMPEG="$BIN/ffmpeg" VIDEO_FFPROBE="$BIN/ffprobe" VIDEO_STORAGE_PATH="$DADOS"
[ -n "$CLAUDE_BIN" ] && export VIDEO_CLAUDE_BIN="$CLAUDE_BIN"
export VIDEO_NARRACAO_MODELOS="$VOZES" VIDEO_ICONES_PATH="$ICONES"
[ -n "$MOTION_PY" ] && export VIDEO_MOTION_PYTHON="$MOTION_PY"
[ -n "$NARRACAO_PY" ] && export VIDEO_NARRACAO_PYTHON="$NARRACAO_PY"
[ -n "$CHATTERBOX_PY" ] && export VIDEO_CHATTERBOX_PYTHON="$CHATTERBOX_PY"
"$PHP" artisan config:clear >/dev/null
"$PHP" artisan video:instalar || true
[ -n "$NARRACAO_PY" ] && { echo "Gerando as amostras das vozes…"; "$PHP" artisan video:vozes || true; }
"$PHP" artisan video:diagnostico || true

# --- 6. .env -----------------------------------------------------------------
passo "6. Cole no .env do STRASA ($APP/.env) e rode: php artisan config:cache"
cat <<ENV

# --- Estúdio de vídeo ---
VIDEO_ATIVO=true
VIDEO_STORAGE_PATH=$DADOS
VIDEO_FFMPEG=$BIN/ffmpeg
VIDEO_FFPROBE=$BIN/ffprobe
VIDEO_THREADS=2
VIDEO_CLAUDE_BIN=${CLAUDE_BIN:-/home/USUARIO/.local/bin/claude}
VIDEO_CLAUDE_HOME=$HOME
# Transcrição local, sem assinatura extra (small = bom equilíbrio em CPU; medium = melhor e mais lento)
VIDEO_TRANSCRICAO=$TRANSCRICAO
VIDEO_PYTHON=${PYTHON:-python3}
VIDEO_WHISPER_MODEL=small
# Narração com voz gerada (local e gratuita)
VIDEO_NARRACAO_MODELOS=$VOZES
VIDEO_NARRACAO_PYTHON=${NARRACAO_PY}
VIDEO_CHATTERBOX_PYTHON=${CHATTERBOX_PY}
# Motor de motion (componentes animados) e ícones
VIDEO_MOTION_PYTHON=${MOTION_PY}
VIDEO_ICONES_PATH=$ICONES
# VIDEO_CLAUDE_TOKEN é gravado pelo scripts/video/logar-claude.sh
ENV

echo
echo "Depois de colar: php artisan config:cache"
echo
read -r -p "Conectar a sua conta do Claude agora? (precisa já ter colado o .env) [s/N] " RESP
if [ "${RESP:-n}" = "s" ] || [ "${RESP:-n}" = "S" ]; then
    "$PHP" artisan config:cache >/dev/null 2>&1 || true
    bash "$APP/scripts/video/logar-claude.sh"
else
    verde "Quando quiser: bash scripts/video/logar-claude.sh"
fi
echo "Lembrete: o cron do cPanel precisa rodar a cada minuto:  cd $APP && $PHP artisan schedule:run >> /dev/null 2>&1"
