<?php

namespace App\Support;

/**
 * HTML do editor (Quill) → texto puro preservando as quebras de linha.
 *
 * Existe porque a legenda do post nasceu dentro do campo de descrição, que é
 * rich text. Uma legenda de rede social é texto puro: o que importa são as
 * quebras, não a marcação. Usado no backfill da coluna `caption` e como
 * reserva no painel do cliente para os cards antigos.
 */
class HtmlParaTexto
{
    public static function converter(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Já é texto puro (nenhuma tag): devolve como está.
        if (! preg_match('/<[a-z!\/]/i', $html)) {
            return trim(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $texto = preg_replace('#<(br|hr)\s*/?>#i', "\n", $html);

        // Fim de bloco vira quebra; o colapso mais abaixo tira o excesso.
        $texto = preg_replace('#</(p|div|li|h[1-6]|blockquote|tr)\s*>#i', "\n", $texto);

        // Bullet de lista não ordenada, para a legenda não virar um bloco só.
        $texto = preg_replace('#<li\b[^>]*>#i', '• ', $texto);

        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // O Quill preenche parágrafo vazio com &nbsp;, que vira espaço fixo.
        $texto = str_replace("\xc2\xa0", ' ', $texto);

        // Espaços à direita de cada linha e no fim das quebras.
        $texto = preg_replace('/[ \t]+(\r?\n)/', '$1', $texto);
        $texto = str_replace("\r\n", "\n", $texto);

        // No máximo uma linha em branco entre parágrafos.
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto);

        return trim($texto);
    }
}
