<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * The look of the game. The colours and fonts of each one live in resources/css/app.css.
 */
enum GameTheme: string implements HasDescription, HasLabel
{
    case Fortis = 'fortis';
    case Classic = 'classic';
    case Checkers = 'checkers';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fortis => 'Fortis',
            self::Classic => 'Nocne niebo',
            self::Checkers => 'Warcaby',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Fortis => 'Kolory fortis.pl: granat i limonka, font Ubuntu. Domyślny.',
            self::Classic => 'Granatowo-fioletowe niebo, złoto i gwiazdy.',
            self::Checkers => 'Czarno-biała szachownica jak flaga wyścigowa, z czerwonymi akcentami.',
        };
    }
}
