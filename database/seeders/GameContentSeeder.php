<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Puzzle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fills in the player's game: the intro and closing texts, the music and the nine pictures,
 * each with a text before it (a riddle about the next bike part) and after it ("Masz już…").
 *
 * The picture and music files are in the repository, so a fresh server only needs this seeder.
 * Missing pictures are created at their position; existing ones keep their picture. Only empty
 * fields are filled (plus messages still left from the previous version of this seeder), so
 * texts changed in the panel survive a re-run.
 */
class GameContentSeeder extends Seeder
{
    public const string INTRO_TEXT = '<h2>Wszystkiego najlepszego na 62 urodziny, Szefie!!!!!</h2><p>Wiemy, że w zarządzaniu Fortisem składasz wszystko w idealną całość... ale... Dzisiaj sprawdzimy Twoje umiejętności na wirtualnym torze przeszkód! Ekipa zrzuciła się na konkretne dofinansowanie do Twojego marzenia, które mocno przyspieszy Twoje tętno<br />(i nie jest to raport kwartalny)</p><p>Ułóż puzzle, odkryj do czego chcielibyśmy się dołożyć! 🧩</p>';

    public const string FINALE_TEXT = '<h2>Szefie, to Twój nowy rower! 🚲⚡</h2><p>Cała ekipa Fortisu zrzuciła się na konkretne dofinansowanie do Twojego wymarzonego e-roweru. Niech każdy kilometr daje tyle frajdy, co ułożenie tych puzzli!</p><p>Sto lat, szerokiej drogi i wiatru zawsze w plecy! 🎉</p>';

    /**
     * Files in public/music.
     *
     * @var array<string, string>
     */
    public const array MUSIC = [
        'music_path' => '01M3SZVYQD1RWXDMKNK671GG9R.mp3',
        'finale_music_path' => '01M3SZVYQNNBPC7M1J7HGV8YVP.mp3',
        'completion_sound_path' => '01M3SZVYQRF74RSVPGQ9AXZ1YD.mp3',
    ];

    /**
     * Pictures 1 to 9, in order: the file in public/puzzles, the text before it and the one after.
     *
     * @var list<array{image: string, lead: string, message: string}>
     */
    public const array PUZZLES = [
        [
            'image' => '01M3SRXQX5XWDYN84BWKCQDFM3.webp',
            'lead' => 'Zbierasz części do czegoś wyjątkowego, a każdy ułożony obrazek to jedna z nich. Na dobry początek coś, bez czego nie ruszysz w żadną trasę…',
            'message' => 'Masz już bidon! 💧 Wszystko zaczyna się od dobrego balansu… i porządnego nawodnienia. Grasz dalej?',
        ],
        [
            'image' => '01M3SRY88W8KZ7J8FFBND1VAHA.webp',
            'lead' => 'Teraz coś, dzięki czemu utrzymasz kurs. Z ekranem, bo przecież lubisz mieć wszystko pod kontrolą.',
            'message' => 'Masz już kierownicę z licznikiem! W Fortisie trzymasz stery, a tutaj mocno chwycisz za kierownicę. Grasz dalej?',
        ],
        [
            'image' => '01M3SRYGX2X42VRB7T5RWH4F34.webp',
            'lead' => 'Gdzie schowasz klucze, telefon i kanapkę na drogę? Ułóż i sprawdź.',
            'message' => 'Masz już torbę na ramę! Zmieści się wszystko… nawet raport kwartalny, jeśli naprawdę musisz. 😉 Grasz dalej?',
        ],
        [
            'image' => '01M3SRZ13XMA8PPVAKV3MT1T17.webp',
            'lead' => 'Bez tego daleko nie zajedziesz. A na pewno nie z przodu.',
            'message' => 'Masz już przednie koło! Najlepiej smakuje wolność, gdy wiatr wieje prosto w twarz. Grasz dalej?',
        ],
        [
            'image' => '01M3SSYNAJTK8ATCBEBBM627RD.webp',
            'lead' => 'Para do poprzedniego etapu, tym razem z tyłu. I z czymś, co pomoże bezpiecznie wyhamować.',
            'message' => 'Masz już tylne koło z hamulcem tarczowym! Zatrzymasz się tylko wtedy, kiedy sam zechcesz. Grasz dalej?',
        ],
        [
            'image' => '01M3SRZEWZC0HEHK3QMXWJX5FV.webp',
            'lead' => 'Serce całej maszyny. Tu zaczyna się prawdziwa moc.',
            'message' => 'Masz już napęd z pedałami! ⚡ Paliwo drożeje, ale ten napęd karmi się prądem i Twoją energią. Grasz dalej?',
        ],
        [
            'image' => '01M3SRZQDHX3V2ETSZEW3CC9M1.webp',
            'lead' => 'Coś dla wygody na długich trasach. Nawet Szef czasem musi usiąść.',
            'message' => 'Masz już siodełko! Najlepsze widoki są wtedy, gdy jedziesz własnym torem. Grasz dalej?',
        ],
        [
            'image' => '01M3SRZZQ5603X5CJXX1PA5G30.webp',
            'lead' => 'Wszystko musi się na czymś trzymać. Zgadnij, czyje logo tu znajdziesz?',
            'message' => 'Masz już ramę, oczywiście z logo Fortisu! Wszystkie części zebrane. Zostało tylko złożyć całość…',
        ],
        [
            'image' => '01M3SS0CBPWFQWY5WMTCQB001C.webp',
            'lead' => 'Ostatnia prosta! Ułóż całość i zobacz, na co zbierała cała ekipa.',
            'message' => 'Masz cały rower! 🚲 Czas wrzucić wyższy bieg na te 62. urodziny!',
        ],
    ];

    /**
     * Messages the first version of this seeder wrote; they are replaced, not kept as edits.
     *
     * @var list<string>
     */
    private const array PREVIOUS_MESSAGES = [
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
            $this->fillGame($game);

            $puzzles = $game->puzzles->keyBy('position');

            foreach (self::PUZZLES as $index => $content) {
                $position = $index + 1;
                $puzzle = $puzzles->get($position);

                if ($puzzle !== null) {
                    $this->fillPuzzle($puzzle, $content);

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
                    'lead_message' => $content['lead'],
                    'message' => $content['message'],
                ]);
            }
        });
    }

    private function fillGame(Game $game): void
    {
        $changes = array_filter([
            'intro_text' => blank($game->intro_text) ? self::INTRO_TEXT : null,
            // A draft that still has the "[prezent]" gap is not a finished text.
            'finale_text' => blank($game->finale_text) || str_contains($game->finale_text, '[prezent]') ? self::FINALE_TEXT : null,
        ]);

        foreach (self::MUSIC as $column => $file) {
            if (blank($game->{$column}) && Storage::disk('music')->exists($file)) {
                $changes[$column] = $file;
            }
        }

        if ($changes !== []) {
            $game->update($changes);
        }
    }

    /**
     * @param  array{image: string, lead: string, message: string}  $content
     */
    private function fillPuzzle(Puzzle $puzzle, array $content): void
    {
        $changes = [];

        if (blank($puzzle->lead_message)) {
            $changes['lead_message'] = $content['lead'];
        }

        if (blank($puzzle->message) || in_array($puzzle->message, self::PREVIOUS_MESSAGES, true)) {
            $changes['message'] = $content['message'];
        }

        if ($changes !== []) {
            $puzzle->update($changes);
        }
    }
}
