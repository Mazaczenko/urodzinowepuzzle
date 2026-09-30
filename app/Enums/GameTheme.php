<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * The look of the game. The colours and fonts of each one live in resources/css/app.css.
 */
enum GameTheme: string implements HasDescription, HasLabel
{
    case Classic = 'classic';
    case Checkers = 'checkers';
    case Fortis = 'fortis';

    public function getLabel(): string
    {
        return match ($this) {
            self::Classic => 'Podstawowy',
            self::Checkers => 'Warcaby',
            self::Fortis => 'Fortis',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Classic => 'Nocne niebo, złoto i gwiazdy.',
            self::Checkers => 'Czarno-biała szachownica jak flaga wyścigowa, z czerwonymi akcentami.',
            self::Fortis => 'Kolory fortis.pl: granat i limonka, font Ubuntu.',
        };
    }
}
