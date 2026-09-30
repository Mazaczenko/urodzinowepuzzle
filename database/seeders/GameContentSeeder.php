<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Puzzle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fills in the player's game: the intro text and the nine pictures with their messages.
 *
 * The picture files are in the repository (public/puzzles), so a fresh server only needs this seeder.
 * Missing pictures are created at their position; existing ones keep their picture and only get a
 * message if they have none. Texts changed later in the panel survive a re-run.
 */
class GameContentSeeder extends Seeder
{
    public const string INTRO_TEXT = '<h2>Wszystkiego najlepszego na 62 urodziny, Szefie!!!!!</h2><p>Wiemy, że w zarządzaniu Fortisem składasz wszystko w idealną całość... ale... Dzisiaj sprawdzimy Twoje umiejętności na wirtualnym torze przeszkód! Ekipa zrzuciła się na konkretne dofinansowanie do Twojego marzenia, które mocno przyspieszy Twoje tętno<br />(i nie jest to raport kwartalny)</p><p>Ułóż puzzle, odkryj do czego chcielibyśmy się dołożyć! 🧩</p>';

    /**
     * Pictures 1 to 9, in order: the file in public/puzzles and the message shown after it.
     *
     * @var list<array{image: string, message: string}>
     */
    public const array PUZZLES = [
        ['image' => '01M3SRXQX5XWDYN84BWKCQDFM3.webp', 'message' => 'Wszystko zaczyna się od dobrego balansu... i w firmie, i w życiu.'],
        ['image' => '01M3SRY88W8KZ7J8FFBND1VAHA.webp', 'message' => 'Czas wrzucić wyższy bieg na te 62. urodziny!'],
        ['image' => '01M3SRYGX2X42VRB7T5RWH4F34.webp', 'message' => 'Najlepiej smakuje wolność, gdy wiatr wieje prosto w twarz.'],
        ['image' => '01M3SRZ13XMA8PPVAKV3MT1T17.webp', 'message' => 'Czasami trzeba mocno nacisnąć na pedał, żeby ruszyć z miejsca.'],
        ['image' => '01M3SSYNAJTK8ATCBEBBM627RD.webp', 'message' => 'W Fortisie trzymasz stery, tutaj musisz mocno chwycić za kierownicę.'],
        ['image' => '01M3SRZEWZC0HEHK3QMXWJX5FV.webp', 'message' => 'Najlepsze widoki są wtedy, gdy jedziesz własnym torem.'],
        ['image' => '01M3SRZQDHX3V2ETSZEW3CC9M1.webp', 'message' => 'To nie są zwykłe zakręty życiowe – to czysta przyjemność z jazdy!'],
        ['image' => '01M3SRZZQ5603X5CJXX1PA5G30.webp', 'message' => 'Paliwo drożeje, ale ten napęd karmi się wyłącznie Twoją energią.'],
        ['image' => '01M3SS0CBPWFQWY5WMTCQB001C.webp', 'message' => 'Ostatnia prosta! Ułóż całość i zobacz, na co zbierała cała ekipa.'],
    ];

    public function run(): void
    {
        Game::query()->with('puzzles')->each(function (Game $game): void {
            if (blank($game->intro_text)) {
                $game->update(['intro_text' => self::INTRO_TEXT]);
            }

            $puzzles = $game->puzzles->keyBy('position');

            foreach (self::PUZZLES as $index => $content) {
                $position = $index + 1;
                $puzzle = $puzzles->get($position);

                if ($puzzle !== null) {
                    if (blank($puzzle->message)) {
                        $puzzle->update(['message' => $content['message']]);
                    }

                    continue;
                }

                if (! Storage::disk('puzzles')->exists($content['image'])) {
                    $this->command?->warn("Gra #{$game->id}: brak pliku public/puzzles/{$content['image']}, obrazek {$position} pominięty.");

                    continue;
                }

                Puzzle::create([
                    'game_id' => $game->id,
                    'position' => $position,
                    'image_path' => $content['image'],
                    'message' => $content['message'],
                ]);
            }
        });
    }
}
