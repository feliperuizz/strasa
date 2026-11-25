<?php

namespace App\Video\Plano;

use RuntimeException;

/** O plano não pôde ser consertado. $problemas volta para o Claude corrigir. */
class PlanoInvalido extends RuntimeException
{
    public function __construct(public readonly array $problemas)
    {
        parent::__construct('Plano de edição inválido: '.implode(' ', $problemas));
    }
}
