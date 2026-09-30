<?php

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
        ->and(GameResource::missingParts($incomplete))->toBe('Brakuje: obrazki (3 z 9), teksty przy obrazkach (3 bez tekstu)');
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
        ->and($game->puzzles()->count())->toBe(9)
        ->and($game->puzzles()->whereNotNull('solved_at')->orWhereNotNull('solve_seconds')->count())->toBe(0);
});

test('uploaded pictures are cropped to 5:2 and stored as webp', function () {
    Storage::fake('puzzles');

    $path = Puzzle::storeImage(UploadedFile::fake()->image('photo.jpg', 2000, 1500));

    [$width, $height] = getimagesizefromstring(Storage::disk('puzzles')->get($path));

    expect($path)->toEndWith('.webp')
        ->and([$width, $height])->toBe([Puzzle::IMAGE_WIDTH, Puzzle::IMAGE_HEIGHT]);
});

test('new pictures are appended and a tenth cannot be added', function () {
    $game = Game::factory()->has(Puzzle::factory()->count(8))->create();

    expect(Puzzle::factory()->for($game)->create()->position)->toBe(9);

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
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 9));
});

test('a picture can be moved one place earlier or later', function () {
    $game = Game::factory()->ready()->create();
    [$first, $second, $third] = [$game->puzzles[0], $game->puzzles[1], $game->puzzles[2]];

    $this->actingAs($this->admin);

    $manager = Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->assertTableActionHidden('moveEarlier', $first)
        ->assertTableActionHidden('moveLater', $game->puzzles[8])
        ->callTableAction('moveLater', $first);

    expect($game->puzzles()->take(3)->pluck('id')->all())->toBe([$second->id, $first->id, $third->id]);

    $manager->callTableAction('moveEarlier', $third);

    expect($game->puzzles()->take(3)->pluck('id')->all())->toBe([$second->id, $third->id, $first->id])
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 9));
});

test('deleting a picture removes its file and closes the gap', function () {
    Storage::fake('puzzles');

    $game = Game::factory()->ready()->create();
    $second = $game->puzzles[1];
    Storage::disk('puzzles')->put($second->image_path, 'image');

    $second->delete();

    Storage::disk('puzzles')->assertMissing($second->image_path);
    expect($game->puzzles()->pluck('position')->all())->toBe(range(1, 8));
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

test('the content seeder fills in the intro and the message of each picture by position', function () {
    $game = Game::factory()
        ->has(Puzzle::factory()->count(9)->state(['message' => null]))
        ->create(['intro_text' => null, 'finale_text' => '<p>Finał</p>']);

    $this->seed(GameContentSeeder::class);

    $game->refresh();

    expect($game->intro_text)->toBe(GameContentSeeder::INTRO_TEXT)
        ->and($game->finale_text)->toBe('<p>Finał</p>')
        ->and($game->puzzles()->pluck('message')->all())->toBe(GameContentSeeder::PUZZLE_MESSAGES)
        ->and($game->isReady())->toBeTrue();
});

test('the content seeder keeps texts changed in the panel and handles missing pictures', function () {
    $game = Game::factory()
        ->has(Puzzle::factory()->count(3)->state(['message' => null]))
        ->create(['intro_text' => '<p>Własny wstęp</p>']);
    $game->puzzles[1]->update(['message' => 'Zmienione w panelu']);

    $this->seed(GameContentSeeder::class);

    expect($game->fresh()->intro_text)->toBe('<p>Własny wstęp</p>')
        ->and($game->puzzles()->pluck('message')->all())->toBe([
            GameContentSeeder::PUZZLE_MESSAGES[0],
            'Zmienione w panelu',
            GameContentSeeder::PUZZLE_MESSAGES[2],
        ]);
});

test('an admin can switch the look of the game', function () {
    $game = Game::factory()->create();

    expect($game->fresh()->theme)->toBe(GameTheme::Classic);

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->fillForm(['theme' => GameTheme::Checkers->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($game->fresh()->theme)->toBe(GameTheme::Checkers);
});
