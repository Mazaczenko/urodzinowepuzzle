<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\Puzzle;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GameProgress extends Widget
{
    protected static string $view = 'filament.widgets.game-progress';

    protected int|string|array $columnSpan = 'full';

    /**
     * The game being edited; on the dashboard the first game is shown instead.
     */
    public ?Model $record = null;

    /**
     * @return array{game: ?Game, puzzles: Collection<int, Puzzle>, solvedCount: int, missingParts: ?string}
     */
    protected function getViewData(): array
    {
        $game = $this->record instanceof Game ? $this->record->fresh() : Game::query()->oldest('id')->first();
        $puzzles = $game?->puzzles()->get() ?? collect();

        return [
            'game' => $game,
            'puzzles' => $puzzles,
            'solvedCount' => $puzzles->whereNotNull('solved_at')->count(),
            'missingParts' => $game ? GameResource::missingParts($game) : null,
        ];
    }

    /**
     * Format a solve time as minutes and seconds.
     */
    public static function formatSeconds(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
