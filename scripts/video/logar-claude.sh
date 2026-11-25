#!/usr/bin/env bash
# =============================================================================
# Conecta a SUA conta do Claude (a assinatura da agência) ao servidor.
#
# Por SSH, como o usuário do cPanel:
#
#     cd ~/public_html/strasa && bash scripts/video/logar-claude.sh
#
# Como funciona (o servidor não precisa de navegador nem de porta aberta):
#   1. O Claude mostra um LINK aqui no terminal.
#   2. Você abre o link no SEU computador/celular, entra com a conta da
#      assinatura e autoriza. A página mostra um CÓDIGO.
#   3. Você cola o código aqui. O Claude devolve um TOKEN (sk-ant-…).
#   4. Você cola o token quando for pedido. O sistema testa e grava no .env.
#
# Rode de novo para renovar (o token vale ~1 ano) ou trocar de conta.
# =============================================================================
set -euo pipefail

APP="$(cd "$(dirname "$0")/../.." && pwd)"
PHP="${PHP:-php}"
cd "$APP"

CLAUDE=""
for c in "$(command -v claude 2>/dev/null || true)" "$HOME/.local/bin/claude"; do
    if [ -n "$c" ] && [ -x "$c" ]; then CLAUDE="$c"; break; fi
done

if [ -z "$CLAUDE" ]; then
    echo "Claude Code não está instalado. Instalando (instalador oficial, sem root)…"
    curl -fsSL https://claude.ai/install.sh | bash
    CLAUDE="$HOME/.local/bin/claude"
fi

echo
echo "Claude Code: $("$CLAUDE" --version 2>/dev/null || echo '?')  ($CLAUDE)"
echo
echo "================================================================"
echo " 1) Vai aparecer um link. Abra no seu computador ou celular."
echo " 2) Entre com a conta da ASSINATURA do Claude e autorize."
echo " 3) Copie o código que a página mostrar e cole aqui."
echo " 4) No fim aparece o token (sk-ant-...). COPIE o token."
echo "================================================================"
echo
read -r -p "Pronto para começar? [Enter] " _

"$CLAUDE" setup-token

echo
echo "Agora cole o token que acabou de aparecer (ele não é mostrado enquanto você cola)."
"$PHP" artisan video:conectar-claude --bin="$CLAUDE"

echo
echo "Conferindo o estúdio inteiro…"
"$PHP" artisan video:diagnostico || true
