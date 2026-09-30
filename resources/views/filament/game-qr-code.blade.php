{{-- Layout uses inline styles: the panel's stylesheet only ships the utility classes Filament itself uses. --}}
{{-- The code sits on a small PRL-era telegram form, like the printed telegram (resources/views/telegram.blade.php). --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=special-elite:400|roboto-condensed:400,700&display=swap" rel="stylesheet" />

<div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center;">
    <div style="
        position: relative;
        width: 100%;
        max-width: 22rem;
        padding: 1rem 1.25rem 1.1rem 1.9rem;
        color: #b3362c;
        background-color: #efe4c2;
        background-image:
            radial-gradient(ellipse at 15% 10%, rgb(255 255 255 / 45%), transparent 55%),
            radial-gradient(ellipse at 90% 95%, rgb(140 100 40 / 18%), transparent 55%);
        box-shadow: inset 0 0 40px rgb(120 85 30 / 28%), 0 10px 25px rgb(0 0 0 / 25%);
        font-family: 'Roboto Condensed', 'Arial Narrow', sans-serif;
        overflow: hidden;
    ">
        {{-- Perforated tear-off edge. --}}
        <span aria-hidden="true" style="position: absolute; inset: 0 auto 0 0.55rem; width: 0.4rem; background: radial-gradient(circle, rgb(43 38 32 / 75%) 0.1rem, transparent 0.12rem) 0 0 / 0.4rem 0.8rem repeat-y;"></span>
        <span aria-hidden="true" style="position: absolute; inset: 0 auto 0 1.2rem; border-left: 1px dashed rgb(179 54 44 / 45%);"></span>

        <div style="display: flex; justify-content: space-between; align-items: end; border-bottom: 2px solid #b3362c; padding-bottom: 0.35rem; margin-bottom: 0.75rem;">
            <span style="font-size: 0.6rem; line-height: 1.3; letter-spacing: 0.08em; text-transform: uppercase; text-align: left;">
                <strong style="display: block; font-size: 0.68rem;">Urząd telegraficzny</strong>
                Fortis · okienko nr 62
            </span>
            <span style="font-weight: 700; font-size: 1.6rem; line-height: 1; letter-spacing: 0.28em;">TELEGRAM</span>
        </div>

        <div style="display: flex; align-items: end; gap: 0.4rem; font-size: 0.62rem; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 0.75rem;">
            <span>Adresat</span>
            <span style="flex: 1; border-bottom: 1px solid #b3362c; text-align: left; padding-left: 0.25rem; font: 0.85rem 'Special Elite', 'Courier New', monospace; color: #23262e;">OB. {{ mb_strtoupper($player) }}</span>
        </div>

        <div style="border: 1.5px solid #b3362c; padding: 0.6rem; display: flex; flex-direction: column; align-items: center; gap: 0.4rem;">
            <span style="font-size: 0.6rem; letter-spacing: 0.08em; text-transform: uppercase;">Załącznik — pilne</span>
            <div style="line-height: 0; max-width: 13rem; width: 100%; margin: 0.4rem 0;">
                {!! str_replace('<svg ', '<svg style="width: 100%; height: auto; display: block;" ', $inkSvg) !!}
            </div>
            <span style="font: 0.8rem 'Special Elite', 'Courier New', monospace; color: #23262e; text-transform: uppercase;">Zeskanuj telefonem stop</span>
        </div>

        {{-- The rubber stamp, pressed a little crooked over the corner. --}}
        <svg viewBox="0 0 100 100" aria-hidden="true" style="position: absolute; right: 0.4rem; bottom: 0.3rem; width: 4.5rem; height: 4.5rem; transform: rotate(-14deg); color: #5a3d8c; opacity: 0.7; mix-blend-mode: multiply; pointer-events: none;">
            <defs>
                <path id="qr-stamp-ring" d="M50,50 m-36,0 a36,36 0 1,1 72,0 a36,36 0 1,1 -72,0" />
            </defs>
            <circle cx="50" cy="50" r="47" fill="none" stroke="currentColor" stroke-width="2.2" />
            <circle cx="50" cy="50" r="29" fill="none" stroke="currentColor" stroke-width="1.4" />
            <text font-family="Roboto Condensed, sans-serif" font-size="9.5" font-weight="700" letter-spacing="1.6" fill="currentColor">
                <textPath href="#qr-stamp-ring">URZĄD TELEGRAFICZNY ✶ FORTIS ✶</textPath>
            </text>
            <text x="50" y="54" text-anchor="middle" font-family="Roboto Condensed, sans-serif" font-size="10" font-weight="700" fill="currentColor">62 LATA</text>
        </svg>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Po zeskanowaniu {{ $player }} od razu jest zalogowany i trafia na ekran startowy.
    </p>

    <code class="text-xs" style="word-break: break-all;">{{ $url }}</code>

    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;">
        <x-filament::button tag="a" :href="'data:image/png;base64,'.base64_encode($png)" download="kod-qr-urodzinowe-puzzle.png" icon="heroicon-o-arrow-down-tray">
            Pobierz PNG
        </x-filament::button>
        <x-filament::button tag="a" color="gray" :href="'data:image/svg+xml;base64,'.base64_encode($svg)" download="kod-qr-urodzinowe-puzzle.svg" icon="heroicon-o-arrow-down-tray">
            Pobierz SVG
        </x-filament::button>
        <x-filament::button tag="a" color="gray" :href="$telegramUrl" target="_blank" icon="heroicon-o-printer">
            Cały telegram do druku
        </x-filament::button>
    </div>

    <p class="text-xs text-warning-600 dark:text-warning-400">
        Kto ma ten kod, ten może grać jako {{ $player }}. Jeśli trafi w niepowołane ręce, wygeneruj nowy.
    </p>
</div>
