<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** VIDEO_ATIVO=false desliga o Estúdio de vídeo inteiro (rotas viram 404). */
class EstudioAtivo
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('video.ativo'), 404);

        return $next($request);
    }
}
