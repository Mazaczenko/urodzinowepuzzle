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
            self::Easy => 'Każdy obrazek ma 9 kawałków, a w ramce cały czas widać podpowiedź.',
            self::Growing => 'Obrazki 1–2 po 9 kawałków, 3–4 po 16, 5–6 po 20. Podpowiedź w ramce słabnie i na koniec znika. Domyślny.',
            self::Hard => 'Obrazki 1–2 po 16 kawałków, 3–4 po 20, 5–6 po 25. Podpowiedź tylko na początku.',
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
     * Pictures are square, so n×n gives square pieces; 5×4 pieces are only slightly taller.
     *
     * @return array{columns: int, rows: int}
     */
    public function grid(int $position): array
    {
        $grids = match ($this) {
            self::Easy => [[3, 3], [3, 3], [3, 3]],
            self::Growing => [[3, 3], [4, 4], [5, 4]],
            self::Hard => [[4, 4], [5, 4], [5, 5]],
        };

        [$columns, $rows] = $grids[min(2, intdiv(max($position, 1) - 1, 2))];

        return ['columns' => $columns, 'rows' => $rows];
    }
}
