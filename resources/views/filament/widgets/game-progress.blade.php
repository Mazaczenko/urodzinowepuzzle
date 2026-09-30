{{-- Layout uses inline styles: the panel's stylesheet only ships the utility classes Filament itself uses. --}}
<x-filament-widgets::widget>
    <x-filament::section heading="Postęp gracza" icon="heroicon-o-chart-bar">
        <div wire:poll.10s>
            @if ($game === null)
                <p class="text-sm text-gray-500 dark:text-gray-400">Nie ma jeszcze żadnej gry.</p>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 1rem;">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Gracz</div>
                        <div class="font-medium">{{ $game->user?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Poziom</div>
                        <div class="font-medium">
                            @if ($game->completed_at)
                                Ukończona 🎉
                            @else
                                {{ min($solvedCount + 1, max($puzzles->count(), 1)) }} / {{ \App\Models\Game::PUZZLES_COUNT }}
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Rozpoczęta</div>
                        <div class="font-medium">{{ $game->started_at?->format('d.m.Y H:i') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ukończona</div>
                        <div class="font-medium">{{ $game->completed_at?->format('d.m.Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                @if ($missingParts)
                    <p class="text-sm font-medium text-warning-600 dark:text-warning-400" style="margin-top: 1rem;">
                        Gra nie jest jeszcze gotowa. {{ $missingParts }}.
                    </p>
                @endif

                @if ($puzzles->isNotEmpty())
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(5.5rem, 1fr)); gap: 0.5rem; margin-top: 1rem;">
                        @foreach ($puzzles as $puzzle)
                            <div @class([
                                'rounded-lg border px-2 py-2 text-center text-sm',
                                'border-success-500/40 bg-success-50 dark:bg-success-500/10' => $puzzle->isSolved(),
                                'border-gray-200 dark:border-white/10' => ! $puzzle->isSolved(),
                            ])>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Obrazek {{ $loop->iteration }}</div>
                                <div class="font-medium tabular-nums">
                                    {{ $puzzle->isSolved() ? \App\Filament\Widgets\GameProgress::formatSeconds($puzzle->solve_seconds) : '—' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
