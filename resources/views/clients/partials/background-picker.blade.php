@props(['client' => null])

@php
    $initialBgType = old('bg_type', $client->bg_type ?? 'default');
    $initialBgColor = old('bg_color', $client->bg_color ?? '#0f172a');
    $initialBgGradient = old('bg_gradient', $client->bg_gradient ?? 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%)');
@endphp

<div x-data="clientBackgroundPicker({
    bgType: '{{ $initialBgType }}',
    bgColor: '{{ $initialBgColor }}',
    bgGradient: '{{ $initialBgGradient }}'
})" class="rounded-xl border border-ink-600 bg-ink-900/60 p-4 sm:p-5 space-y-4">

    <!-- Cabeçalho da Seção -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-ink-600/70 pb-3">
        <div>
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="text-base">🎨</span> Padrão Visual e Plano de Fundo
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
                Escolha o estilo de fundo para os quadros, projetos e visualizações deste cliente.
            </p>
        </div>

        <!-- Seletor de Tipo (Padrão, Cor Sólida, Gradiente) -->
        <div class="inline-flex rounded-lg bg-ink-800 p-1 border border-ink-600 text-xs self-start sm:self-auto">
            <button type="button" @click="setType('default')"
                    :class="bgType === 'default' ? 'bg-brand-600 text-white font-medium shadow' : 'text-slate-400 hover:text-white'"
                    class="rounded-md px-3 py-1.5 transition">
                Padrão
            </button>
            <button type="button" @click="setType('color')"
                    :class="bgType === 'color' ? 'bg-brand-600 text-white font-medium shadow' : 'text-slate-400 hover:text-white'"
                    class="rounded-md px-3 py-1.5 transition flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full border border-white/40" :style="'background-color: ' + bgColor"></span>
                Cor Fixa
            </button>
            <button type="button" @click="setType('gradient')"
                    :class="bgType === 'gradient' ? 'bg-brand-600 text-white font-medium shadow' : 'text-slate-400 hover:text-white'"
                    class="rounded-md px-3 py-1.5 transition flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full border border-white/40" :style="'background: ' + bgGradient"></span>
                Gradiente
            </button>
        </div>
    </div>

    <!-- Inputs Ocultos submetidos no Formulário -->
    <input type="hidden" name="bg_type" :value="bgType">
    <input type="hidden" name="bg_color" :value="bgColor">
    <input type="hidden" name="bg_gradient" :value="bgGradient">

    <!-- ======================= OPÇÃO 1: PADRÃO ======================= -->
    <div x-show="bgType === 'default'" x-cloak class="text-xs text-slate-400 py-2">
        <div class="flex items-center gap-2 text-slate-300">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>Fundo escuro padrão do sistema (Slate/Dark Ink). Simples, limpo e profissional.</span>
        </div>
    </div>

    <!-- ======================= OPÇÃO 2: COR FIXA / SÓLIDA ======================= -->
    <div x-show="bgType === 'color'" x-cloak class="space-y-3">
        <div>
            <span class="text-xs font-medium text-slate-300">Paleta de Cores Sólidas Recomendadas:</span>
            <div class="grid grid-cols-5 sm:grid-cols-10 gap-2 mt-2">
                <template x-for="c in solidPresets" :key="c.value">
                    <button type="button" @click="bgColor = c.value"
                            :title="c.name + ' (' + c.value + ')'"
                            class="group relative h-9 w-full rounded-lg border transition transform hover:scale-105 flex items-center justify-center"
                            :class="bgColor.toLowerCase() === c.value.toLowerCase() ? 'border-brand-400 ring-2 ring-brand-500/50 scale-105 shadow-md' : 'border-ink-600 hover:border-slate-400'"
                            :style="'background-color: ' + c.value">
                        <span x-show="bgColor.toLowerCase() === c.value.toLowerCase()" class="text-white text-xs drop-shadow font-bold">✓</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Seletor customizado de cor -->
        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-ink-700/60">
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-300">Cor Personalizada:</label>
                <div class="flex items-center gap-2 bg-ink-900 px-2 py-1 rounded-lg border border-ink-600">
                    <input type="color" x-model="bgColor" class="h-6 w-7 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="bgColor" maxlength="7" class="w-20 bg-transparent text-xs font-mono text-white focus:outline-none uppercase">
                </div>
            </div>
            <span class="text-[11px] text-slate-500">Dica: Tons escuros proporcionam o melhor contraste para os cartões de tarefas.</span>
        </div>
    </div>

    <!-- ======================= OPÇÃO 3: GRADIENTE ======================= -->
    <div x-show="bgType === 'gradient'" x-cloak class="space-y-4">
        <!-- Presets de Gradientes -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-300">Galeria de Gradientes Sofisticados:</span>
                <span class="text-[11px] text-slate-500">Clique para aplicar</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                <template x-for="g in gradientPresets" :key="g.name">
                    <button type="button" @click="applyGradientPreset(g.value)"
                            class="group relative h-16 rounded-xl border p-2 text-left transition transform hover:scale-[1.02] flex flex-col justify-end overflow-hidden"
                            :class="bgGradient === g.value ? 'border-brand-400 ring-2 ring-brand-500/50 shadow-lg' : 'border-ink-600/80 hover:border-slate-400'"
                            :style="'background: ' + g.value">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition"></div>
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-white drop-shadow truncate pr-1" x-text="g.name"></span>
                            <span x-show="bgGradient === g.value" class="text-white text-xs drop-shadow font-bold">✓</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Criador de Gradiente Personalizado -->
        <div class="rounded-lg border border-ink-700 bg-ink-900/80 p-3 space-y-3" x-data="{ customOpen: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="customOpen = !customOpen">
                <span class="text-xs font-medium text-slate-300 flex items-center gap-1.5">
                    <span>✨</span> Criar Gradiente Personalizado
                </span>
                <button type="button" class="text-xs text-brand-400 hover:text-brand-300" x-text="customOpen ? 'Recolher ▲' : 'Configurar Cores ▼'"></button>
            </div>

            <div x-show="customOpen" x-cloak class="grid gap-3 sm:grid-cols-3 pt-2 border-t border-ink-700">
                <div>
                    <label class="text-[11px] text-slate-400 block mb-1">Cor Inicial</label>
                    <div class="flex items-center gap-2 bg-ink-800 px-2 py-1 rounded border border-ink-600">
                        <input type="color" x-model="gradColor1" @input="updateCustomGradient()" class="h-6 w-6 rounded cursor-pointer border-0 bg-transparent p-0">
                        <input type="text" x-model="gradColor1" @input="updateCustomGradient()" maxlength="7" class="w-16 bg-transparent text-xs font-mono text-white focus:outline-none uppercase">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] text-slate-400 block mb-1">Cor Final</label>
                    <div class="flex items-center gap-2 bg-ink-800 px-2 py-1 rounded border border-ink-600">
                        <input type="color" x-model="gradColor2" @input="updateCustomGradient()" class="h-6 w-6 rounded cursor-pointer border-0 bg-transparent p-0">
                        <input type="text" x-model="gradColor2" @input="updateCustomGradient()" maxlength="7" class="w-16 bg-transparent text-xs font-mono text-white focus:outline-none uppercase">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] text-slate-400 block mb-1">Direção</label>
                    <select x-model="gradAngle" @change="updateCustomGradient()" class="w-full bg-ink-800 text-xs text-white rounded border border-ink-600 px-2 py-1.5 focus:outline-none">
                        <option value="135deg">Diagonal (135°)</option>
                        <option value="90deg">Horizontal (90°)</option>
                        <option value="180deg">Vertical (180°)</option>
                        <option value="45deg">Diagonal Inversa (45°)</option>
                        <option value="radial">Radial Central</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================= LIVE PREVIEW INTERATIVO ======================= -->
    <div class="pt-3 border-t border-ink-600/70">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-slate-300 flex items-center gap-1.5">
                <span>👁️</span> Pré-visualização em Tempo Real do Quadro do Cliente:
            </span>
            <span class="text-[11px] px-2 py-0.5 rounded bg-ink-800 border border-ink-600 text-slate-300 font-mono"
                  x-text="bgType === 'default' ? 'Padrão (Slate)' : (bgType === 'color' ? 'Cor Fixa (' + bgColor + ')' : 'Gradiente Ativo')"></span>
        </div>

        <!-- Mockup Visual em Miniatura -->
        <div class="relative rounded-xl border border-ink-600 overflow-hidden shadow-inner transition-all duration-300 p-3 min-h-[140px] flex flex-col justify-between"
             :style="getComputedStyle()">
            
            <!-- Mini Header Mockup -->
            <div class="flex items-center justify-between bg-ink-800/80 backdrop-blur-md rounded-lg px-3 py-1.5 border border-white/10 shadow-sm mb-3">
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded bg-brand-500 grid place-items-center text-[9px] font-bold text-white">C</span>
                    <span class="text-xs font-semibold text-white">Exemplo de Cliente</span>
                    <span class="text-[10px] text-slate-400">/ Rede Social 2026</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span class="text-[10px] text-slate-300">No prazo</span>
                </div>
            </div>

            <!-- Mini Kanban Columns Mockup -->
            <div class="grid grid-cols-3 gap-2 flex-1">
                <!-- Coluna 1 -->
                <div class="rounded-lg bg-ink-800/85 backdrop-blur-md p-2 border border-white/10 shadow-sm flex flex-col gap-1.5">
                    <div class="flex items-center justify-between border-b border-ink-700/60 pb-1">
                        <span class="text-[10px] font-semibold text-slate-300 uppercase">A Fazer</span>
                        <span class="text-[9px] text-slate-400 bg-ink-900/60 px-1 rounded">2</span>
                    </div>
                    <div class="rounded bg-ink-900/90 p-1.5 border border-ink-700/60 shadow-xs">
                        <div class="text-[10px] font-medium text-slate-200 truncate">Planejamento de Posts</div>
                        <div class="text-[8px] text-slate-400 mt-0.5">18/Ago · #Design</div>
                    </div>
                </div>

                <!-- Coluna 2 -->
                <div class="rounded-lg bg-ink-800/85 backdrop-blur-md p-2 border border-white/10 shadow-sm flex flex-col gap-1.5">
                    <div class="flex items-center justify-between border-b border-ink-700/60 pb-1">
                        <span class="text-[10px] font-semibold text-amber-400 uppercase">Em Andamento</span>
                        <span class="text-[9px] text-slate-400 bg-ink-900/60 px-1 rounded">1</span>
                    </div>
                    <div class="rounded bg-ink-900/90 p-1.5 border border-ink-700/60 shadow-xs">
                        <div class="text-[10px] font-medium text-slate-200 truncate">Gravação de Reels</div>
                        <div class="text-[8px] text-slate-400 mt-0.5">20/Ago · #Vídeo</div>
                    </div>
                </div>

                <!-- Coluna 3 -->
                <div class="rounded-lg bg-ink-800/85 backdrop-blur-md p-2 border border-white/10 shadow-sm flex flex-col gap-1.5">
                    <div class="flex items-center justify-between border-b border-ink-700/60 pb-1">
                        <span class="text-[10px] font-semibold text-emerald-400 uppercase">Postado</span>
                        <span class="text-[9px] text-slate-400 bg-ink-900/60 px-1 rounded">1</span>
                    </div>
                    <div class="rounded bg-ink-900/90 p-1.5 border border-ink-700/60 shadow-xs">
                        <div class="text-[10px] font-medium text-slate-200 truncate">Carrossel Semanal</div>
                        <div class="text-[8px] text-emerald-400 mt-0.5">✓ Publicado</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function clientBackgroundPicker(config) {
        return {
            bgType: config.bgType || 'default',
            bgColor: config.bgColor || '#0f172a',
            bgGradient: config.bgGradient || 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%)',
            
            gradColor1: '#0f172a',
            gradColor2: '#312e81',
            gradAngle: '135deg',

            solidPresets: [
                { name: 'Noturno Escuro', value: '#0f172a' },
                { name: 'Obsidiana', value: '#09090b' },
                { name: 'Carvão Slate', value: '#18181b' },
                { name: 'Índigo Noturno', value: '#1e1b4b' },
                { name: 'Azul Meia-Noite', value: '#082f49' },
                { name: 'Esmeralda Dark', value: '#022c22' },
                { name: 'Roxo Profundo', value: '#2e1065' },
                { name: 'Vinho Tinto', value: '#450a0a' },
                { name: 'Rocha Quente', value: '#1c1917' },
                { name: 'Cinza Asfalto', value: '#1e293b' }
            ],

            gradientPresets: [
                { name: 'Índigo Cósmico', value: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%)' },
                { name: 'Floresta Esmeralda', value: 'linear-gradient(135deg, #022c22 0%, #064e3b 50%, #0f172a 100%)' },
                { name: 'Nebulosa Roxa', value: 'linear-gradient(135deg, #18181b 0%, #3b0764 50%, #1e1b4b 100%)' },
                { name: 'Oceano Profundo', value: 'linear-gradient(135deg, #082f49 0%, #0c4a6e 50%, #020617 100%)' },
                { name: 'Crepúsculo Sunset', value: 'linear-gradient(135deg, #3b0764 0%, #701a75 50%, #18181b 100%)' },
                { name: 'Brasa Escura', value: 'linear-gradient(135deg, #450a0a 0%, #292524 50%, #0c0a09 100%)' },
                { name: 'Aurora Boreal', value: 'linear-gradient(135deg, #042f2e 0%, #134e4a 50%, #1e1b4b 100%)' },
                { name: 'Cyberpunk Slate', value: 'linear-gradient(135deg, #030712 0%, #111827 50%, #1f2937 100%)' },
                { name: 'Mocha Dark', value: 'linear-gradient(135deg, #1c1917 0%, #292524 50%, #44403c 100%)' },
                { name: 'Galáxia Violeta', value: 'linear-gradient(135deg, #09090b 0%, #4c1d95 50%, #1e1b4b 100%)' },
                { name: 'Safira Neon', value: 'linear-gradient(135deg, #030712 0%, #1e3a8a 50%, #0284c7 100%)' },
                { name: 'Amanhecer Dourado', value: 'linear-gradient(135deg, #1c1917 0%, #78350f 50%, #18181b 100%)' }
            ],

            setType(type) {
                this.bgType = type;
            },

            applyGradientPreset(gradientValue) {
                this.bgGradient = gradientValue;
                this.bgType = 'gradient';
            },

            updateCustomGradient() {
                if (this.gradAngle === 'radial') {
                    this.bgGradient = `radial-gradient(circle at center, ${this.gradColor2} 0%, ${this.gradColor1} 100%)`;
                } else {
                    this.bgGradient = `linear-gradient(${this.gradAngle}, ${this.gradColor1} 0%, ${this.gradColor2} 100%)`;
                }
            },

            getComputedStyle() {
                if (this.bgType === 'color') {
                    return `background-color: ${this.bgColor};`;
                }
                if (this.bgType === 'gradient') {
                    return `background: ${this.bgGradient};`;
                }
                return 'background-color: #1e1e1e;';
            }
        };
    }
</script>
