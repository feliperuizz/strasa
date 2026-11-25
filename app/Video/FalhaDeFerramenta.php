<?php

namespace App\Video;

use RuntimeException;

/** Um processo externo (FFmpeg, Claude, Python) falhou. A mensagem é para gente ler. */
class FalhaDeFerramenta extends RuntimeException
{
    public function __construct(string $mensagem, public readonly string $detalhe = '')
    {
        parent::__construct($mensagem);
    }
}
