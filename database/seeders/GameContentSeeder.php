<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

/**
 * Fills in the texts of the player's game: the intro and the message shown after each picture.
 *
 * Only empty fields are filled, so texts changed later in the panel survive a re-run.
 * Pictures are uploaded in the panel first; a message goes to the picture at the same position.
 */
class GameContentSeeder extends Seeder
{
    public const string INTRO_TEXT = '<h2>Wszystkiego najlepszego na 62 urodziny, Szefie!!!!!</h2><p>Wiemy, że w zarządzaniu Fortisem składasz wszystko w idealną całość... ale... Dzisiaj sprawdzimy Twoje umiejętności na wirtualnym torze przeszkód! Ekipa zrzuciła się na konkretne dofinansowanie do Twojego marzenia, które mocno przyspieszy Twoje tętno<br />(i nie jest to raport kwartalny)</p><p>Ułóż puzzle, odkryj do czego chcielibyśmy się dołożyć! 🧩</p>';

    /**
     * Messages for pictures 1 to 9, in order.
     *
     * @var list<string>
     */
    public const array PUZZLE_MESSAGES = [
        'Wszystko zaczyna się od dobrego balansu... i w firmie, i w życiu.',
        'Czas wrzucić wyższy bieg na te 62. urodziny!',
        'Najlepiej smakuje wolność, gdy wiatr wieje prosto w twarz.',
        'Czasami trzeba mocno nacisnąć na pedał, żeby ruszyć z miejsca.',
        'W Fortisie trzymasz stery, tutaj musisz mocno chwycić za kierownicę.',
        'Najlepsze widoki są wtedy, gdy jedziesz własnym torem.',
        'To nie są zwykłe zakręty życiowe – to czysta przyjemność z jazdy!',
        'Paliwo drożeje, ale ten napęd karmi się wyłącznie Twoją energią.',
        'Ostatnia prosta! Ułóż całość i zobacz, na co zbierała cała ekipa.',
    ];

    public function run(): void
    {
        Game::query()->with('puzzles')->each(function (Game $game): void {
            if (blank($game->intro_text)) {
                $game->update(['intro_text' => self::INTRO_TEXT]);
            }

            foreach ($game->puzzles as $puzzle) {
                $message = self::PUZZLE_MESSAGES[$puzzle->position - 1] ?? null;

                if ($message !== null && blank($puzzle->message)) {
                    $puzzle->update(['message' => $message]);
                }
            }

            $missing = count(self::PUZZLE_MESSAGES) - $game->puzzles->count();

            if ($missing > 0) {
                $this->command?->warn("Gra #{$game->id}: brakuje {$missing} obrazków, ich teksty wejdą po ponownym uruchomieniu seedera.");
            }
        });
    }
}
