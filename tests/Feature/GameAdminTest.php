<?php

use App\Filament\Resources\GameResource;
use App\Filament\Resources\GameResource\Pages\CreateGame;
use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Filament\Resources\GameResource\Pages\ListGames;
use App\Filament\Resources\GameResource\RelationManagers\PuzzlesRelationManager;
use App\Models\Game;
use App\Models\Puzzle;
use App\Models\User;
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
    $incomplete = Game::factory()->create(['blik_code' => null]);

    $this->actingAs($this->admin);

    Livewire::test(ListGames::class)
        ->assertTableColumnStateSet('readiness', 'Gotowa', $ready)
        ->assertTableColumnStateSet('readiness', 'Niekompletna', $incomplete);

    expect(GameResource::missingParts($ready))->toBeNull()
        ->and(GameResource::missingParts($incomplete))->toBe('Brakuje: kod BLIK (9 cyfr), obrazki (0 z 9)');
});

test('an admin can create a game for the player', function () {
    $player = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(CreateGame::class)
        ->fillForm(['user_id' => $player->id, 'blik_code' => '012345678', 'wishes' => '<p>Sto lat!</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($player->game)
        ->blik_code->toBe('012345678')
        ->wishes->toBe('<p>Sto lat!</p>');
});

test('saving a game without retyping the code keeps the code', function () {
    $game = Game::factory()->create(['blik_code' => '987654321', 'blik_password' => 'tajne']);

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->assertFormSet(['blik_code' => '987654321', 'blik_password' => 'tajne'])
        ->fillForm(['wishes' => '<p>Nowe życzenia</p>'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($game->fresh())
        ->blik_code->toBe('987654321')
        ->blik_password->toBe('tajne')
        ->wishes->toBe('<p>Nowe życzenia</p>');
});

test('the blik code must be exactly nine digits', function (string $code) {
    $this->actingAs($this->admin);

    Livewire::test(CreateGame::class)
        ->fillForm(['user_id' => User::factory()->create()->id, 'blik_code' => $code])
        ->call('create')
        ->assertHasFormErrors(['blik_code']);

    expect(Game::count())->toBe(0);
})->with(['12345678', '1234567890', '12345678a']);

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
    $game = Game::factory()->completed()->create(['blik_code' => '987654321']);

    $this->actingAs($this->admin);

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->callAction('resetProgress');

    expect($game->fresh())
        ->started_at->toBeNull()
        ->completed_at->toBeNull()
        ->blik_code->toBe('987654321')
        ->and($game->puzzles()->count())->toBe(9)
        ->and($game->puzzles()->whereNotNull('solved_at')->orWhereNotNull('solve_seconds')->count())->toBe(0);
});

test('uploaded pictures are cropped to 5:2 and stored as webp', function () {
    Storage::fake('public');

    $path = Puzzle::storeImage(UploadedFile::fake()->image('photo.jpg', 2000, 1500));

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

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

test('pictures can be reordered', function () {
    $game = Game::factory()->ready()->create();
    $reversed = $game->puzzles->pluck('id')->reverse()->values()->all();

    $this->actingAs($this->admin);

    Livewire::test(PuzzlesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
        ->call('reorderTable', $reversed);

    expect($game->puzzles()->pluck('id')->all())->toBe($reversed)
        ->and($game->puzzles()->pluck('position')->all())->toBe(range(1, 9));
});

test('deleting a picture removes its file and closes the gap', function () {
    Storage::fake('public');

    $game = Game::factory()->ready()->create();
    $second = $game->puzzles[1];
    Storage::disk('public')->put($second->image_path, 'image');

    $second->delete();

    Storage::disk('public')->assertMissing($second->image_path);
    expect($game->puzzles()->pluck('position')->all())->toBe(range(1, 8));
});

test('the seeder creates three admins, the player and an empty game', function () {
    config(['app.seed_users_password' => 'secret-for-tests']);

    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $player = User::firstWhere('email', 'm.kobylinski@fortis.pl');

    expect(User::where('is_admin', true)->whereKeyNot($this->admin->id)->orderBy('email')->pluck('email')->all())
        ->toBe(['m.piorko@fortis.pl', 't.lugowski@fortis.pl', 'w.mazur@fortis.pl'])
        ->and($player->is_admin)->toBeFalse()
        ->and(Hash::check('secret-for-tests', $player->password))->toBeTrue()
        ->and(Game::where('user_id', $player->id)->count())->toBe(1);
});

test('the seeder refuses to run without a password', function () {
    config(['app.seed_users_password' => null]);

    $this->seed(UserSeeder::class);
})->throws(RuntimeException::class);
