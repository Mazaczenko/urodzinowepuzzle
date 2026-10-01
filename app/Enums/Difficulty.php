<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * How many pieces each picture is cut into.
 */
enum Difficulty: string implements HasDescription, HasLabel
{
    case Easy = 'easy';
    case Growing = 'growing';
    case Hard = 'hard';

    public function getLabel(): string
    {
        return match ($this) {
            self::Easy => 'Łatwy',
            self::Growing => 'Rosnący',
            self::Hard => 'Trudny',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Easy => 'Każdy obrazek ma 10 kawałków, a w ramce cały czas widać podpowiedź.',
            self::Growing => 'Obrazki 1–2 po 10 kawałków, 3–4 po 15, 5–6 po 18. Podpowiedź w ramce słabnie i na koniec znika. Domyślny.',
            self::Hard => 'Obrazki 1–2 po 15 kawałków, 3–4 po 18, 5–6 po 24. Podpowiedź tylko na początku.',
        };
    }

    /**
     * How visible the faded picture in the frame is (0 = no hint at all).
     */
    public function hint(int $position): float
    {
        $hints = match ($this) {
            self::Easy => [0.16, 0.16, 0.16],
            self::Growing => [0.16, 0.07, 0.0],
            self::Hard => [0.07, 0.0, 0.0],
        };

        return $hints[min(2, intdiv(max($position, 1) - 1, 2))];
    }

    /**
     * The grid for the picture at the given position (1–6): columns × rows.
     *
     * Pictures are 5:2, so 5×2 gives square pieces; the other grids are close to square
     * and still fit a phone held upright.
     *
     * @return array{columns: int, rows: int}
     */
    public function grid(int $position): array
    {
        $grids = match ($this) {
            self::Easy => [[5, 2], [5, 2], [5, 2]],
            self::Growing => [[5, 2], [5, 3], [6, 3]],
            self::Hard => [[5, 3], [6, 3], [8, 3]],
        };

        [$columns, $rows] = $grids[min(2, intdiv(max($position, 1) - 1, 2))];

        return ['columns' => $columns, 'rows' => $rows];
    }
}
