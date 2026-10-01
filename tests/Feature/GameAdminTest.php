<?php

use App\Enums\Difficulty;
use App\Enums\GameTheme;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\GameResource\Pages\CreateGame;
use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Filament\Resources\GameResource\Pages\ListGames;
use App\Filament\Resources\GameResource\RelationManagers\PuzzlesRelationManager;
use App\Models\Game;
use App\Models\Puzzle;
use App\Models\User;
use Database\Seeders\GameContentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('an admin can open the games list, a game and the dashboard', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs($this->admin);

    $this->get('/admin')->assertOk();
    $this->get(GameResource::getUrl('index'))->assertOk()->assertSee($game->user->name);
    $this->get(GameResource::getUrl('edit', ['record' => $game]))->assertOk();
});

test('the games list shows whether a game is ready', function () {
    $ready = Game::factory()->ready()->create();
    $incomplete = Game::factory()->has(Puzzle::factory()->count(3)->state(['message' => '']))->create();

    $this->actingAs($this->admin);

    Livewire::test(ListGames::class)
        ->assertTableColumnStateSet('readiness', 'Gotowa', $ready)
        ->assertTableColumnStateSet('readiness', 'Niekompletna', $incomplete);

    expect(GameResource::missingParts($ready))->toBeNull()
        ->and(GameResource::missingParts($incomplete))->toBe('Brakuje: obrazki (3 z 6), teksty przy obrazkach (3 bez tekstu)');
});

test('an admin can create a game for the player', function () {
    $player = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(CreateGame::class)
        ->fillForm(['user_id' => $player->id, 'intro_text' => '<p>Zaczynamy!</p>', 'finale_text' => '<p>Sto lat!</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($player->game)
        ->intro_text->toBe('<p>Zaczynamy!</p>')
        ->finale_text->toBe('<p>Sto lat!</p>');
});

test('a game cannot be assigned to an admin or to a player who already has one', function () {
    $taken = Game::factory()->create()->user;

    $this->actingAs($this->admin);

    Livewire::test(CreateGame::class)
        ->fillForm(['user_id' => $taken->id])
        ->call('create')
        ->assertHasFormErrors(['user_id']);

    Livewire::test(CreateGame::class)
        ->fillForm(['user_id' => $this->admin->id])
        ->call('create')
        ->assertHasFormErrors(['user_id']);

    expect(Game::count())->toBe(1);
});

test('resetting progress clears solve times and dates but keeps the content', function () {
    $game = Game::factory()->completed()->create();

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->callAction('resetProgress');

    expect($game->fresh())
        ->started_at->toBeNull()
        ->completed_at->toBeNull()
        ->and($game->puzzles()->count())->toBe(6)
        ->and($game->puzzles()->whereNotNull('solved_at')->orWhereNotNull('solve_seconds')->count())->toBe(0);
});

test('uploaded pictures are cropped to 5:2 and stored as webp', function () {
    Storage::fake('puzzles');

    $path = Puzzle::storeImage(UploadedFile::fake()->image('photo.jpg', 2000, 1500));

    [$width, $height] = getimagesizefromstring(Storage::disk('puzzles')->get($path));

    expect($path)->toEndWith('.webp')
        ->and([$width, $height])->toBe([Puzzle::IMAGE_WIDTH, Puzzle::IMAGE_HEIGHT]);
});

test('new pictures are appended and a seventh cannot be added', function () {
    $game = Game::factory()->has(Puzzle::factory()->count(5))->create();

    expect(Puzzle::factory()->for($game)->create()->position)->toBe(6);

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->assertTableActionHidden('create');
});

test('an admin can edit the message shown after a picture', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->ready()->create();
    $puzzle = $game->puzzles->first();
    Storage::disk('puzzles')->put($puzzle->image_path, 'image');

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->callTableAction('edit', $puzzle, ['message' => "Pamiętasz ten dzień?\nBył wyjątkowy!"])
        ->assertHasNoTableActionErrors();

    expect($puzzle->fresh()->message)->toBe("Pamiętasz ten dzień?\nBył wyjątkowy!");
});

test('an admin can save a picture and go straight on to the next one', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->ready()->create();
    [$first, $second] = [$game->puzzles[0], $game->puzzles[1]];
    $game->puzzles->each(fn (Puzzle $puzzle) => Storage::disk('puzzles')->put($puzzle->image_path, 'image'));

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->mountTableAction('edit', $first)
        ->setTableActionData(['message' => 'Pierwszy tekst'])
        ->callMountedTableAction(['next' => true])
        ->assertHasNoTableActionErrors()
        ->assertTableActionMounted('edit')
        ->assertTableActionDataSet(['message' => $second->message]);

    expect($first->fresh()->message)->toBe('Pierwszy tekst');
});

test('pictures are served from public/puzzles', function () {
    $puzzle = Puzzle::factory()->create(['image_path' => 'abc.webp']);

    expect($puzzle->imageUrl())->toBe(asset('puzzles/abc.webp'))
        ->and(config('filesystems.disks.puzzles.root'))->toBe(public_path('puzzles'));
});

test('pictures can be reordered', function () {
    $game = Game::factory()->ready()->create();
    $reversed = $game->puzzles->pluck('id')->reverse()->values()->all();

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->call('reorderTable', $reversed);

    expect($game->puzzles()->pluck('id')->all())->toBe($reversed)
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 6));
});

test('a picture can be moved one place earlier or later', function () {
    $game = Game::factory()->ready()->create();
    [$first, $second, $third] = [$game->puzzles[0], $game->puzzles[1], $game->puzzles[2]];

    $this->actingAs($this->admin);

    $manager = Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->assertTableActionHidden('moveEarlier', $first)
        ->assertTableActionHidden('moveLater', $game->puzzles[5])
        ->callTableAction('moveLater', $first);

    expect($game->puzzles()->take(3)->pluck('id')->all())->toBe([$second->id, $first->id, $third->id]);

    $manager->callTableAction('moveEarlier', $third);

    expect($game->puzzles()->take(3)->pluck('id')->all())->toBe([$second->id, $third->id, $first->id])
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 6));
});

test('deleting a picture removes its file and closes the gap', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->ready()->create();
    $second = $game->puzzles[1];
    Storage::disk('puzzles')->put($second->image_path, 'image');

    $second->delete();

    Storage::disk('puzzles')->assertMissing($second->image_path);
    expect($game->puzzles()->pluck('position')->all())->toBe(range(1, 5));
});

test('the seeder creates three admins, the player and an empty game', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $player = User::firstWhere('email', 'm.kobylinski@fortis.pl');

    expect(User::where('is_admin', true)->whereKeyNot($this->admin->id)->orderBy('email')->pluck('email')->all())
        ->toBe(['m.piorko@fortis.pl', 't.lugowski@fortis.pl', 'w.mazur@fortis.pl'])
        ->and($player->is_admin)->toBeFalse()
        ->and(Hash::check('4tis4ever!', $player->password))->toBeTrue()
        ->and(Game::where('user_id', $player->id)->count())->toBe(1);
});

test('a picture can be saved without a message', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->callTableAction('create', data: ['image_path' => UploadedFile::fake()->image('photo.jpg', 1000, 400), 'message' => null])
        ->assertHasNoTableActionErrors();

    expect($game->puzzles()->count())->toBe(1)
        ->and($game->puzzles()->first()->message)->toBeNull()
        ->and($game->fresh()->isReady())->toBeFalse();
});

test('the content seeder builds the whole game from the pictures in public/puzzles', function () {
    Storage::fake('puzzles');
    Storage::fake('music');
    collect(GameContentSeeder::PUZZLES)->each(fn (array $content) => Storage::disk('puzzles')->put($content['image'], 'image'));
    collect(GameContentSeeder::MUSIC)->each(fn (string $file) => Storage::disk('music')->put($file, 'mp3'));

    $game = Game::factory()->create(['intro_text' => null, 'finale_text' => '<p>Finał</p>']);

    $this->seed(GameContentSeeder::class);
    $this->seed(GameContentSeeder::class);

    $game->refresh();

    expect($game->intro_text)->toBe(GameContentSeeder::INTRO_TEXT)
        ->and($game->finale_text)->toBe('<p>Finał</p>')
        ->and($game->puzzles()->pluck('image_path')->all())->toBe(array_column(array_slice(GameContentSeeder::PUZZLES, 0, 6), 'image'))
        ->and($game->puzzles()->pluck('message')->all())->toBe(array_column(array_slice(GameContentSeeder::PUZZLES, 0, 6), 'message'))
        ->and($game->puzzles()->pluck('lead_message')->all())->toBe(array_column(array_slice(GameContentSeeder::PUZZLES, 0, 6), 'lead'))
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 6))
        ->and($game->only(array_keys(GameContentSeeder::MUSIC)))->toBe(GameContentSeeder::MUSIC)
        ->and($game->isReady())->toBeTrue();
});

test('the content seeder keeps pictures and texts added in the panel', function () {
    Storage::fake('puzzles');

    $game = Game::factory()
        ->has(Puzzle::factory()->count(3)->state(['message' => null]))
        ->create(['intro_text' => '<p>Własny wstęp</p>']);
    $game->puzzles[1]->update(['message' => 'Zmienione w panelu']);
    $ownPictures = $game->puzzles->pluck('image_path')->all();

    // No picture files on this disk, so the missing pictures 4 to 6 are skipped.
    $this->seed(GameContentSeeder::class);

    expect($game->fresh()->intro_text)->toBe('<p>Własny wstęp</p>')
        ->and($game->puzzles()->pluck('image_path')->all())->toBe($ownPictures)
        ->and($game->puzzles()->pluck('message')->all())->toBe([
            GameContentSeeder::PUZZLES[0]['message'],
            'Zmienione w panelu',
            GameContentSeeder::PUZZLES[2]['message'],
        ]);
});
test('an admin can switch the look of the game', function () {
    $game = Game::factory()->create();

    expect($game->fresh()->theme)->toBe(GameTheme::Fortis);

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->fillForm(['theme' => GameTheme::Checkers->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($game->fresh()->theme)->toBe(GameTheme::Checkers);
});

test('the content seeder fills an empty closing text and replaces its own old messages', function () {
    Storage::fake('puzzles');

    $game = Game::factory()
        ->has(Puzzle::factory()->count(2)->state(['message' => 'Czas wrzucić wyższy bieg na te 62. urodziny!']))
        ->create(['finale_text' => null]);
    $game->puzzles[1]->update(['message' => 'Własny tekst']);

    $this->seed(GameContentSeeder::class);

    expect($game->fresh()->finale_text)->toBe(GameContentSeeder::FINALE_TEXT)
        ->and($game->puzzles()->pluck('message')->all())->toBe([GameContentSeeder::PUZZLES[0]['message'], 'Własny tekst']);
});

test('an admin can set how hard the puzzles are', function () {
    $game = Game::factory()->create();

    expect($game->fresh()->difficulty)->toBe(Difficulty::Growing);

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->fillForm(['difficulty' => Difficulty::Hard->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($game->fresh()->difficulty)->toBe(Difficulty::Hard);
});

test('the pictures get more pieces as the game goes on', function () {
    expect(Difficulty::Growing->grid(1))->toBe(['columns' => 5, 'rows' => 2])
        ->and(Difficulty::Growing->grid(3))->toBe(['columns' => 5, 'rows' => 3])
        ->and(Difficulty::Growing->grid(6))->toBe(['columns' => 6, 'rows' => 3])
        ->and(Difficulty::Easy->grid(6))->toBe(['columns' => 5, 'rows' => 2])
        ->and(Difficulty::Hard->grid(6))->toBe(['columns' => 8, 'rows' => 3]);
});

test('the content seeder replaces a closing text draft that was never finished', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->create(['finale_text' => '<h2>Ekipa dokłada się do [prezent].</h2>']);

    $this->seed(GameContentSeeder::class);

    expect($game->fresh()->finale_text)->toBe(GameContentSeeder::FINALE_TEXT);
});
