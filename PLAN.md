# 🧩 Urodzinowa gra puzzle z czekiem BLIK

Aplikacja urodzinowa: gracz loguje się, układa 9 obrazków po 10 puzzli, a po każdym ułożonym obrazku odsłania kolejną cyfrę 9-cyfrowego czeku BLIK. Na koniec są fajerwerki, życzenia urodzinowe i cały kod.

**Stack:** Laravel + Vue 3 + Inertia.js + Tailwind CSS, panel admina w Filament 3.

---

## Spis treści

1. [Role i konta](#1-role-i-konta)
2. [Mechanika gry](#2-mechanika-gry)
3. [Stack i biblioteki](#3-stack-i-biblioteki)
4. [Model danych](#4-model-danych)
5. [Bezpieczeństwo kodu BLIK](#5-bezpieczeństwo-kodu-blik)
6. [Flow ekranów](#6-flow-ekranów)
7. [Mechanika puzzli (headbreaker)](#7-mechanika-puzzli-headbreaker)
8. [Panel Filament](#8-panel-filament)
9. [Oprawa wizualna i dźwięk](#9-oprawa-wizualna-i-dźwięk)
10. [Etapy pracy](#10-etapy-pracy)
11. [Pułapki](#11-pułapki)
12. [Kod startowy: użytkownicy i role](#12-kod-startowy-użytkownicy-i-role)

---

## 1. Role i konta

| E-mail | Rola | Dostęp |
|---|---|---|
| `t.lugowski@fortis.pl` | admin | panel Filament `/admin` |
| `m.piorko@fortis.pl` | admin | panel Filament `/admin`, dodaje czek BLIK |
| `w.mazur@fortis.pl` | admin | panel Filament `/admin` |
| `m.kobylinski@fortis.pl` | gracz | gra `/` |

Hasło startowe jest ustawiane w `.env` (`SEED_USERS_PASSWORD`), patrz [sekcja 12](#12-kod-startowy-użytkownicy-i-role).

- Admin wchodzący na `/` jest przekierowywany do `/admin`, żeby nie zużyć postępu gracza.
- Gracz nie ma dostępu do `/admin` (`canAccessPanel()` zwraca `false`).

---

## 2. Mechanika gry

- Gracz loguje się i widzi rozsypane puzzle pierwszego obrazka.
- Każdy obrazek składa się z **10 puzzli** (siatka 5×2, na telefonie w pionie 2×5).
- Po ułożeniu obrazka odsłania się **kolejna cyfra** czeku BLIK.
- Obrazków jest **9**, tyle ile cyfr w czeku BLIK.
- W tle gra motywacyjna muzyka.
- Na końcu są fajerwerki, życzenia urodzinowe i **cały kod czeku BLIK** z przyciskiem „Kopiuj”.
- Postęp jest zapisywany w bazie. Po odświeżeniu strony lub zmianie urządzenia gra wraca na właściwy poziom.

---

## 3. Stack i biblioteki

| Warstwa | Wybór | Po co |
|---|---|---|
| Backend | Laravel 11/12 + Breeze (Vue + Inertia) | auth, routing, scaffold |
| Admin | Filament 3 | gra, obrazki, kod, reset postępu |
| Puzzle | `headbreaker` | prawdziwe kształty jigsaw, drag & drop, snap, dotyk |
| Animacje | `gsap` | wjazd puzzli, odsłanianie cyfr, przejścia |
| Fajerwerki | `@fireworks-js/vue` + `canvas-confetti` | finał i mikroefekty |
| Audio | `howler` | muzyka w pętli, fade, efekty dźwiękowe |
| Utility | `@vueuse/core` | `useWindowSize`, `useStorage`, `useWakeLock` |
| Obrazki | `intervention/image` | kompresja i WebP przy uploadzie |
| Style | Tailwind + font display (np. *Fredoka* / *Playfair Display*) | |

```bash
composer require filament/filament:"^3.2" intervention/image
php artisan filament:install --panels

npm i headbreaker gsap howler @fireworks-js/vue canvas-confetti @vueuse/core
```

> Alternatywa dla headbreakera to własne puzzle na SVG `clipPath` z pointer events. Daje pełną kontrolę nad wyglądem, ale kosztuje około 1–2 dni więcej.

---

## 4. Model danych

### `users`

Standardowa tabela Breeze plus kolumna `is_admin` (boolean, domyślnie `false`).

### `games`

```php
$table->id();
$table->foreignId('user_id')->constrained();       // gracz
$table->text('blik_code')->nullable();              // cast: 'encrypted'
$table->text('blik_password')->nullable();          // cast: 'encrypted', jeśli bank wymaga hasła
$table->text('wishes')->nullable();                 // życzenia (RichEditor)
$table->string('music_path')->nullable();
$table->string('finale_music_path')->nullable();
$table->timestamp('started_at')->nullable();
$table->timestamp('completed_at')->nullable();
$table->timestamps();
```

### `puzzles`

```php
$table->id();
$table->foreignId('game_id')->constrained()->cascadeOnDelete();
$table->unsignedTinyInteger('position');            // 1–9
$table->string('image_path');
$table->string('caption')->nullable();              // np. "Nasz pierwszy wyjazd ❤️"
$table->unsignedInteger('solve_seconds')->nullable();
$table->timestamp('solved_at')->nullable();
$table->timestamps();

$table->unique(['game_id', 'position']);
```

### Relacje i helpery

- `User hasOne Game`
- `Game hasMany Puzzle` (sortowane po `position`)
- `Game::currentPuzzle()` zwraca pierwsze nieułożone puzzle
- `Game::revealedDigits()` zwraca cyfry odsłonięte do tej pory
- `Game::isReady()` jest prawdziwe, gdy gra ma 9 obrazków i 9-cyfrowy kod

---

## 5. Bezpieczeństwo kodu BLIK

**Pełny kod nigdy nie trafia do frontendu przed ukończeniem gry.** Inaczej można go podejrzeć w DevTools albo w `data-page` Inertii.

```php
// app/Models/Game.php
protected function casts(): array
{
    return [
        'blik_code'     => 'encrypted',
        'blik_password' => 'encrypted',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];
}

public function currentPuzzle(): ?Puzzle
{
    return $this->puzzles()->whereNull('solved_at')->orderBy('position')->first();
}

public function revealedDigits(): array
{
    $solved = $this->puzzles()->whereNotNull('solved_at')->count();

    return array_slice(str_split((string) $this->blik_code), 0, $solved);
}
```

```php
// app/Http/Controllers/PuzzleController.php
public function solve(Request $request, Puzzle $puzzle)
{
    $game = $request->user()->game;

    abort_unless($puzzle->game_id === $game->id, 403);
    abort_unless($game->currentPuzzle()?->is($puzzle), 422);

    $puzzle->update([
        'solved_at'     => now(),
        'solve_seconds' => $request->integer('seconds'),
    ]);

    if ($game->puzzles()->whereNull('solved_at')->doesntExist()) {
        $game->update(['completed_at' => now()]);

        return to_route('game.finale');
    }

    return to_route('game.play');
}
```

Zasady:

- Props dla `Play.vue` zawierają tylko obrazek **aktualnego** puzzla, `revealedDigits`, numer poziomu i `caption`.
- Pozostałe obrazki nie są wysyłane do frontendu.
- Pełny kod i hasło czeku trafiają tylko do `Finale.vue`, a route `game.finale` wymaga `completed_at !== null`.
- `blik_code` i `blik_password` są szyfrowane w bazie (`encrypted` cast).

---

## 6. Flow ekranów

| Strona (Inertia) | Opis |
|---|---|
| `Auth/Login.vue` | klimatyczny ekran logowania zamiast domyślnego Breeze |
| `Game/Intro.vue` | powitanie i przycisk **Zaczynamy**, który uruchamia muzykę (wymóg autoplay) |
| `Game/Play.vue` | pasek 9 slotów na cyfry, plansza z puzzlami, licznik „Poziom X/9”, mute |
| `Game/Finale.vue` | fajerwerki, życzenia z efektem typewriter, cały kod z przyciskiem „Kopiuj” |

### Routing

```php
Route::middleware('auth')->group(function () {
    Route::get('/', [GameController::class, 'intro'])->name('game.intro');
    Route::get('/play', [GameController::class, 'play'])->name('game.play');
    Route::post('/puzzles/{puzzle}/solve', [PuzzleController::class, 'solve'])->name('puzzles.solve');
    Route::get('/finale', [GameController::class, 'finale'])->name('game.finale');
});
```

Wszystkie akcje w `GameController` zaczynają się od `if ($request->user()->is_admin) return redirect('/admin');`.

### Szczegóły `Play.vue`

- **Góra:** 9 slotów na cyfry. Odkryte są złote, zakryte mają pulsujące „?”.
- **Środek:** plansza z 10 rozsypanymi elementami i półprzezroczystym konturem obrazka jako podpowiedzią.
- **Róg:** mute/unmute (`useStorage`) i licznik poziomu.

### Odsłonięcie cyfry

1. Po ułożeniu obrazek się „scala”: GSAP usuwa krawędzie puzzli.
2. Wyświetla się `caption` obrazka.
3. Cyfra wlatuje do slotu z efektem flip i małym confetti.
4. Przycisk „Dalej” robi POST `puzzles.solve`.

---

## 7. Mechanika puzzli (headbreaker)

```js
// resources/js/Components/JigsawBoard.vue (szkic)
import headbreaker from 'headbreaker'

onMounted(() => {
  const img = new Image()
  img.src = props.imageUrl
  img.onload = () => {
    canvas = new headbreaker.Canvas(el.value.id, {
      width,
      height,
      pieceSize: pieceSize.value,
      proximity: 20,          // tolerancja snapa
      borderFill: 10,
      strokeWidth: 1.5,
      lineSoftness: 0.18,
      image: img,
      preventOffstageDrag: true,
      fixed: true,
    })

    canvas.adjustImagesToPuzzleHeight()
    canvas.autogenerate({
      horizontalPiecesCount: isPortrait.value ? 2 : 5,
      verticalPiecesCount: isPortrait.value ? 5 : 2,
    })
    canvas.shuffle(0.8)
    canvas.draw()

    canvas.attachSolvedValidator()
    canvas.onConnect(() => sound.snap.play())
    canvas.onValid(() => emit('solved', elapsedSeconds()))
  }
})
```

- Orientację wybiera `useWindowSize()`: 5×2 poziomo, 2×5 pionowo.
- Obrazki są przycinane w Filamencie do jednej proporcji (`imageCropAspectRatio`).
- Kontener canvasu ma `touch-action: none`, żeby przeciąganie nie przewijało strony.

---

## 8. Panel Filament

### Dostęp

`User implements FilamentUser`, a `canAccessPanel()` zwraca `$this->is_admin`.

### `GameResource`

- Wybór gracza (`Select` użytkowników z `is_admin = false`).
- `blik_code`: `TextInput` z `->length(9)->numeric()->password()->revealable()`.
- `blik_password`: opcjonalnie, `->password()->revealable()`.
- `wishes`: `RichEditor`.
- `music_path` i `finale_music_path`: `FileUpload` z `->acceptedFileTypes(['audio/mpeg'])`.

### Relation manager `Puzzles`

- `image_path`: `FileUpload` z `->image()->imageEditor()->imageCropAspectRatio('5:2')`.
- `caption`: `TextInput`.
- Kolejność przez `->reorderable('position')`.

### Widget „Postęp”

- Aktualny poziom gracza.
- Czas ułożenia każdego puzzla.
- Data rozpoczęcia i ukończenia gry.

### Akcje

- **Resetuj postęp** czyści `solved_at`, `solve_seconds`, `started_at` i `completed_at`.
- **Podgląd jako gracz** to tryb testowy, który nie zapisuje postępu.
- **Badge gotowości** w tabeli pokazuje, czy gra ma 9 obrazków i kompletny kod.

---

## 9. Oprawa wizualna i dźwięk

### Wygląd

- **Tło:** ciemny granat/fiolet z gradientem i wolno dryfującymi piórkami lub gwiazdkami (SVG + CSS animation).
- **Akcenty:** złoto i róż.
- **Plansza:** karta w stylu glassmorphism (`backdrop-blur`, `bg-white/10`, `border-white/20`).
- **Mikrointerakcje:** element unosi się i rzuca cień przy przeciąganiu, a po połączeniu robi krótki „bump” z dźwiękiem.
- **Obrazki:** wspólne zdjęcia z `caption` zamieniają grę w małą historię.

### Dźwięk

- Motywujący utwór w pętli: Howler, `loop: true`, fade-in 2 s.
- Osobny, wzruszający utwór na finał, z crossfade przy wejściu w `Finale`.
- Efekty: klik przy chwyceniu, snap przy połączeniu, fanfara przy odsłonięciu cyfry.

---

## 10. Etapy pracy

| # | Zakres | Czas |
|---|---|---|
| 1 | Laravel + Breeze Vue/Inertia, migracje, modele, seedery | 0,5 dnia |
| 2 | Filament: `GameResource`, relation manager, upload, reset, widget | 0,5 dnia |
| 3 | `JigsawBoard.vue` z headbreakerem, responsywność, dotyk | 1 dzień |
| 4 | Flow Play → solve → reveal, pasek cyfr, zabezpieczenie propsów | 0,5 dnia |
| 5 | Audio, Intro, Finale z fajerwerkami i typewriterem | 0,5–1 dzień |
| 6 | Szlif wizualny, testy na docelowym telefonie, deploy | 0,5 dnia |

**Łącznie:** około 3–4 dni robocze.

### Checklist przed oddaniem

- [ ] 9 obrazków wgranych i przyciętych
- [ ] Czek BLIK wygenerowany i wpisany (9 cyfr), ważny w dniu urodzin
- [ ] Hasło czeku wpisane, jeśli bank go wymaga
- [ ] Życzenia wpisane
- [ ] Muzyka wgrana (gra + finał)
- [ ] Przejście całej gry kontem testowym, potem **reset postępu**
- [ ] Test na telefonie gracza (iOS/Android)
- [ ] Hasła kont zmienione lub przekazane właściwym osobom

---

## 11. Pułapki

- **Ważność czeku BLIK** jest ograniczona w czasie i zależy od banku. Wygeneruj go tak, żeby był ważny w dniu urodzin, i sprawdź datę w aplikacji bankowej.
- **iOS Safari** uruchamia audio tylko po geście użytkownika i pauzuje je przy zablokowaniu ekranu. Wznawiaj muzykę na `visibilitychange`.
- **Scroll na mobile:** canvas puzzli musi mieć `touch-action: none`.
- **Wagi obrazków:** kompresuj je do około 1600 px szerokości w WebP przy uploadzie.
- **Deploy na home.pl:** buduj assety lokalnie (`npm run build`) i wrzucaj `public/build`.
- **Sesja:** ustaw długi `SESSION_LIFETIME` albo domyślnie zaznaczone „remember me”, żeby gracza nie wylogowało w połowie.
- **`env()` w seederze:** przy `config:cache` zwraca `null`, więc seeder wtedy użyje wartości domyślnej.

---

## 12. Kod startowy: użytkownicy i role

### `.env`

```dotenv
SEED_USERS_PASSWORD="WszysktiegoNajlepszego2026!"
```

### Migracja `is_admin`

`database/migrations/2026_09_30_000001_add_is_admin_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
```

### Seeder

`database/seeders/UserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make(env('SEED_USERS_PASSWORD', 'WszysktiegoNajlepszego2026!'));

        $users = [
            // Admini – panel Filament
            ['email' => 't.lugowski@fortis.pl',   'name' => 'T. Ługowski',   'is_admin' => true],
            ['email' => 'm.piorko@fortis.pl',     'name' => 'M. Piórko',     'is_admin' => true],
            ['email' => 'w.mazur@fortis.pl',      'name' => 'W. Mazur',      'is_admin' => true],

            // Gracz – układa puzzle
            ['email' => 'm.kobylinski@fortis.pl', 'name' => 'M. Kobyliński', 'is_admin' => false],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'              => $user['name'],
                    'is_admin'          => $user['is_admin'],
                    'password'          => $password,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
```

W `DatabaseSeeder::run()`:

```php
$this->call(UserSeeder::class);
```

### Model `User`

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    protected $fillable = ['name', 'email', 'password', 'is_admin', 'email_verified_at'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function game()
    {
        return $this->hasOne(Game::class);
    }
}
```

### Uruchomienie

```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
```
