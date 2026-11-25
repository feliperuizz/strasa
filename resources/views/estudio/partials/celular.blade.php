{{--
    Celular de amostra com a identidade de vídeo do cliente. Estático (estúdio)
    ou ao vivo: passe $alpine = true dentro de um x-data com "p" (o formulário).
--}}
@php
    $alpine = $alpine ?? false;
    $logoUrl = $perfil->logo_path ? null : $client->logo_url;
    $familias = collect(\App\Models\VideoProfile::FONTES)->map(fn ($f) => 'family='.str_replace(' ', '+', $f).':wght@400;700;800')->implode('&');
@endphp
@once
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $familias }}&display=swap">
@endonce
<div class="mx-auto w-[210px]">
    <div class="relative aspect-[9/16] overflow-hidden rounded-[26px] border-[5px] border-black bg-black shadow-2xl ring-1 ring-white/10"
         @if($alpine) :style="`background: radial-gradient(120% 80% at 80% 10%, ${p.cor_primaria} 0%, ${p.cor_fundo} 62%)`"
         @else style="background: radial-gradient(120% 80% at 80% 10%, {{ $perfil->cor_primaria }} 0%, {{ $perfil->cor_fundo }} 62%)" @endif>

        {{-- "Imagem" de fundo: silhueta genérica de quem fala --}}
        <svg class="absolute inset-x-0 bottom-0 h-[62%] w-full opacity-[0.22]" viewBox="0 0 100 100" preserveAspectRatio="xMidYMax meet" aria-hidden="true">
            <circle cx="50" cy="34" r="17" fill="#fff"/><path d="M14 100c0-22 16-36 36-36s36 14 36 36z" fill="#fff"/>
        </svg>

        {{-- Logo --}}
        <div class="absolute" @if($alpine) x-show="p.logo_posicao !== 'nenhum'" :class="{
                'left-3 top-3': p.logo_posicao === 'topo_esquerda', 'right-3 top-3': p.logo_posicao === 'topo_direita',
                'left-3 bottom-[22%]': p.logo_posicao === 'baixo_esquerda', 'right-3 bottom-[22%]': p.logo_posicao === 'baixo_direita' }"
             :style="`width: ${p.logo_tamanho * 2}px; opacity: ${p.logo_opacidade / 100}`"
             @else style="{{ match($perfil->logo_posicao) { 'topo_esquerda' => 'left:12px;top:12px', 'baixo_esquerda' => 'left:12px;bottom:22%', 'baixo_direita' => 'right:12px;bottom:22%', 'nenhum' => 'display:none', default => 'right:12px;top:12px' } }};width: {{ $perfil->logo_tamanho * 2 }}px;opacity: {{ $perfil->logo_opacidade / 100 }}" @endif>
            @if($perfil->logo_path || $logoUrl)
                <img src="{{ $logoUrl ?? route('clients.estudio.logo', $client).'?v='.$perfil->updated_at?->timestamp }}" class="w-full" alt="">
            @else
                <span class="grid aspect-square w-full place-items-center rounded-full text-[10px] font-bold" @if($alpine) :style="`background:${p.cor_destaque};color:${p.cor_fundo}`" @else style="background:{{ $perfil->cor_destaque }};color:{{ $perfil->cor_fundo }}" @endif>LOGO</span>
            @endif
        </div>

        {{-- Título de amostra --}}
        <p class="absolute inset-x-3 top-[19%] text-center text-[17px] font-extrabold leading-tight"
           @if($alpine) :style="`font-family:'${p.fonte_titulo}';color:${p.cor_texto};text-shadow:0 2px 6px #0009`"
           @else style="font-family:'{{ $perfil->fonteTituloEfetiva() }}';color:{{ $perfil->cor_texto }};text-shadow:0 2px 6px #0009" @endif>
            3 dicas de <span @if($alpine) :style="`color:${p.cor_destaque}`" @else style="color:{{ $perfil->cor_destaque }}" @endif>ouro</span>
        </p>

        {{-- Legenda de amostra --}}
        <div class="absolute inset-x-2 text-center"
             @if($alpine) :style="`top:${p.legenda_posicao === 'alto' ? '28%' : (p.legenda_posicao === 'centro' ? '50%' : '68%')}`"
             @else style="top:{{ ['alto' => '28%', 'centro' => '50%'][$perfil->legenda_posicao] ?? '68%' }}" @endif>
            <span class="inline-block px-1.5 py-0.5 text-[15px] font-extrabold leading-tight"
                  @if($alpine) :class="p.legenda_estilo === 'simples' ? 'text-[11px] font-bold' : ''"
                  :style="`font-family:'${p.fonte_titulo}';color:${p.cor_texto};${p.legenda_estilo === 'caixa' ? 'background:' + p.cor_primaria + ';border-radius:4px;' : 'text-shadow:-2px -2px 0 #000,2px -2px 0 #000,-2px 2px 0 #000,2px 2px 0 #000'};text-transform:${p.legenda_caixa_alta ? 'uppercase' : 'none'}`"
                  @else style="font-family:'{{ $perfil->fonteTituloEfetiva() }}';color:{{ $perfil->cor_texto }};{{ $perfil->legenda_estilo === 'caixa' ? 'background:'.$perfil->cor_primaria.';border-radius:4px;' : 'text-shadow:-2px -2px 0 #000,2px -2px 0 #000,-2px 2px 0 #000,2px 2px 0 #000' }};text-transform:{{ $perfil->legenda_caixa_alta ? 'uppercase' : 'none' }}" @endif>
                olha <span @if($alpine) :style="p.legenda_estilo === 'simples' ? '' : `color:${p.cor_destaque}`" @else style="{{ $perfil->legenda_estilo === 'simples' ? '' : 'color:'.$perfil->cor_destaque }}" @endif>isso</span> aqui
            </span>
        </div>

        {{-- Botões do app: a zona que a edição evita --}}
        <div class="absolute bottom-[9%] right-2 flex flex-col items-center gap-2 opacity-60">
            <span class="h-4 w-4 rounded-full bg-white/70"></span><span class="h-4 w-4 rounded-full bg-white/70"></span><span class="h-4 w-4 rounded-full bg-white/70"></span>
        </div>
        <div class="absolute inset-x-3 bottom-[4%] space-y-1 opacity-50">
            <span class="block h-1.5 w-2/3 rounded bg-white/70"></span><span class="block h-1.5 w-1/2 rounded bg-white/50"></span>
        </div>
    </div>
</div>
