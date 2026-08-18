@props([
    'beams' => 2,
    'thickness' => 2,
    'radius' => 16,
    'glow' => true,
    'idleSpeed' => 42,
    'hoverSpeed' => 240,
    'brandColor' => '#6366f1', // Indigo (brand)
    'accentColor' => '#0ea5e9' // Sky (accent)
])

@php
    $uid = uniqid('beam_');
@endphp

<div x-data="borderBeamPanel_{{ $uid }}({ idleSpeed: {{ $idleSpeed }}, hoverSpeed: {{ $hoverSpeed }}, stiffness: 30, damping: 11 })"
     x-on:mouseenter="surge()"
     x-on:mouseleave="settle()"
     x-on:focusin="surge()"
     x-on:focusout="settle()"
     {{ $attributes->merge(['class' => "relative w-full border border-ink-600 bg-[#111827] mk-beam-$uid"]) }}
     style="border-radius: {{ $radius }}px; isolation: isolate;"
     x-bind:style="`--mk-beam-a: ${angle.toFixed(2)}deg; border-radius: {{ $radius }}px; isolation: isolate;`">
    
    <style>
        .mk-beam-{{ $uid }} .mk-beam-ring, .mk-beam-{{ $uid }} .mk-beam-glow {
            position: absolute;
            inset: -1px;
            border-radius: {{ $radius }}px;
            pointer-events: none;
            background: conic-gradient(from var(--mk-beam-a, 0deg), 
                transparent 0deg, 
                color-mix(in srgb, {{ $brandColor }} 4%, transparent) 18deg, 
                color-mix(in srgb, {{ $brandColor }} 55%, transparent) 46deg, 
                {{ $brandColor }} 56deg, 
                color-mix(in srgb, {{ $brandColor }} 22%, #ffffff) 60deg, 
                transparent 63deg
                @if($beams === 2)
                , transparent 198deg, 
                color-mix(in srgb, {{ $accentColor }} 4%, transparent) 216deg, 
                color-mix(in srgb, {{ $accentColor }} 50%, transparent) 244deg, 
                {{ $accentColor }} 254deg, 
                color-mix(in srgb, {{ $accentColor }} 26%, #ffffff) 258deg, 
                transparent 261deg
                @endif
                , transparent 360deg);
        }
        .mk-beam-{{ $uid }} .mk-beam-ring {
            padding: {{ $thickness }}px;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
        }
        .mk-beam-{{ $uid }} .mk-beam-glow { 
            filter: blur(14px); 
            opacity: 0.35; 
            z-index: -1; 
        }
        @media (forced-colors: active) {
            .mk-beam-{{ $uid }} .mk-beam-ring, .mk-beam-{{ $uid }} .mk-beam-glow { display: none; }
            .mk-beam-{{ $uid }} { border-color: CanvasText; }
        }
    </style>
    
    @if($glow)
        <div aria-hidden="true" class="mk-beam-glow"></div>
    @endif
    <div aria-hidden="true" class="mk-beam-ring"></div>
    
    <div class="relative z-10 w-full h-full">
        {{ $slot }}
    </div>
</div>

@push('scripts')
@once
<script>
    if (typeof Spring === 'undefined') {
        class Spring {
            constructor(value, k, d) {
                this.x = value; 
                this.target = value; 
                this.k = k; 
                this.d = d; 
                this.v = 0;
            }
            step(dt) {
                const a = this.k * (this.target - this.x) - this.d * this.v;
                this.v += a * dt;
                this.x += this.v * dt;
                return this.x;
            }
        }
        window.Spring = Spring;
    }
</script>
@endonce

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('borderBeamPanel_{{ $uid }}', (config) => ({
            angle: 0,
            speed: null,
            raf: null,
            lastTime: 0,
            init() {
                this.speed = new window.Spring(config.idleSpeed, config.stiffness, config.damping);
                this.loop = this.loop.bind(this);
                // Start animation
                if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                    this.raf = requestAnimationFrame(this.loop);
                } else {
                    this.angle = 40; // Static angle
                }
            },
            loop(now) {
                if (!this.lastTime) this.lastTime = now;
                let dt = (now - this.lastTime) / 1000;
                dt = Math.min(Math.max(dt, 0), 0.05); // clamp
                this.lastTime = now;
                
                this.angle += this.speed.step(dt) * dt;
                this.angle = ((this.angle % 360) + 360) % 360; // wrap
                
                this.raf = requestAnimationFrame(this.loop);
            },
            surge() {
                if(this.speed) this.speed.target = config.hoverSpeed;
            },
            settle() {
                if(this.speed) this.speed.target = config.idleSpeed;
            },
            destroy() {
                if (this.raf) cancelAnimationFrame(this.raf);
            }
        }));
    });
</script>
@endpush
