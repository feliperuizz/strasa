#!/usr/bin/env bash
# =============================================================================
# Estúdio de vídeo do STRASA — preparação do servidor (VPS com cPanel)
#
# Rode UMA vez, por SSH, logado como o usuário do cPanel (não precisa de root):
#
#     cd ~/public_html/strasa && bash scripts/video/instalar-servidor.sh
#
# Opção:   --sem-whisper   não instala a transcrição local (sem legenda automática)
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
for arg in "$@"; do
    case "$arg" in
        --sem-whisper) COM_WHISPER=0 ;;
        *) echo "Opção desconhecida: $arg"; exit 1 ;;
    esac
done

APP="$(cd "$(dirname "$0")/../.." && pwd)"
BASE="$HOME/strasa-video"
BIN="$BASE/bin"
DADOS="$BASE/dados"
PHP="${PHP:-php}"
mkdir -p "$BIN" "$DADOS"

verde() { printf '\033[32m%s\033[0m\n' "$*"; }
amarelo() { printf '\033[33m%s\033[0m\n' "$*"; }
vermelho() { printf '\033[31m%s\033[0m\n' "$*"; }
passo() { printf '\n\033[1m== %s\033[0m\n' "$*"; }

passo "Sistema"
echo "Usuário: $(whoami)   Home: $HOME   App: $APP"
echo "Arquitetura: $(uname -m)   Núcleos: $(nproc 2>/dev/null || echo '?')"
if [ -r /proc/meminfo ]; then
    awk '/MemTotal|MemAvailable/ {printf "%s %.1f GB\n", $1, $2/1048576}' /proc/meminfo
fi
df -h "$HOME" | tail -1 | awk '{print "Disco na home: " $4 " livres de " $2}'

# --- 1. FFmpeg ---------------------------------------------------------------
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
        python3 -c 'import sys, tarfile; tarfile.open(sys.argv[1]).extractall(sys.argv[2])' "$TMP/ffmpeg.tar.xz" "$TMP"
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
PYTHON=""
for py in python3.12 python3.11 python3.10 python3.9 python3; do
    if command -v "$py" >/dev/null 2>&1 && "$py" -c 'import sys; sys.exit(0 if sys.version_info >= (3, 9) else 1)' 2>/dev/null; then PYTHON="$(command -v "$py")"; break; fi
done
TRANSCRICAO=faster_whisper
if [ "$COM_WHISPER" = 0 ]; then
    TRANSCRICAO=nenhum; amarelo "Pulado (--sem-whisper): vídeos sairão sem legenda automática."
elif [ -z "$PYTHON" ]; then
    TRANSCRICAO=nenhum
    vermelho "Precisa de Python 3.9+ (achei: $(python3 --version 2>&1 || echo nenhum)). No cPanel/WHM peça ao suporte ou instale python3.11; depois rode este script de novo."
else
    "$PYTHON" -m pip install --user --quiet --upgrade pip >/dev/null 2>&1 || true
    if "$PYTHON" -m pip install --user --quiet faster-whisper; then
        verde "faster-whisper instalado em $PYTHON (o modelo 'small', ~500 MB, baixa na primeira legenda)."
    else
        TRANSCRICAO=nenhum; vermelho "Falhou ao instalar o faster-whisper. Veja a mensagem acima."
    fi
fi

# --- 4. Rede -----------------------------------------------------------------
passo "4. Saída para a internet (porta 443)"
for host in api.anthropic.com claude.ai cdn.jsdelivr.net huggingface.co; do
    if curl -s -o /dev/null --max-time 8 "https://$host"; then echo "  ✓ $host"; else vermelho "  ✗ $host bloqueado — libere a SAÍDA na porta 443 no firewall (CSF: TCP_OUT)."; fi
done
echo "Nenhuma porta de entrada é necessária."

# --- 5. Estúdio --------------------------------------------------------------
passo "5. Fontes, efeitos sonoros e diagnóstico"
cd "$APP"
export VIDEO_FFMPEG="$BIN/ffmpeg" VIDEO_FFPROBE="$BIN/ffprobe" VIDEO_STORAGE_PATH="$DADOS"
[ -n "$CLAUDE_BIN" ] && export VIDEO_CLAUDE_BIN="$CLAUDE_BIN"
"$PHP" artisan config:clear >/dev/null
"$PHP" artisan video:instalar || true
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
