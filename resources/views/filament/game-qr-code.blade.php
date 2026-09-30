{{-- Layout uses inline styles: the panel's stylesheet only ships the utility classes Filament itself uses. --}}
<div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center;">
    <div style="background: #fff; padding: 0.75rem; border-radius: 0.75rem; line-height: 0;">
        {!! $svg !!}
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
    </div>

    <p class="text-xs text-warning-600 dark:text-warning-400">
        Kto ma ten kod, ten może grać jako {{ $player }}. Jeśli trafi w niepowołane ręce, wygeneruj nowy.
    </p>
</div>
