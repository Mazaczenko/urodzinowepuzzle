<?php

use App\Models\Game;
use App\Models\Puzzle;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login screen', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with(['/', '/play', '/finale']);

test('admins are sent to the panel instead of the game', function (string $uri) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get($uri)->assertRedirect('/admin');
})->with(['/', '/play', '/finale']);

test('an admin cannot solve puzzles', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('puzzles.solve', $game->puzzles->first()))
        ->assertRedirect('/admin');

    expect($game->solvedPuzzlesCount())->toBe(0);
});

test('the player cannot enter the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

test('the player sees a waiting screen until the game is ready', function (Closure $makeGame) {
    $player = User::factory()->create();
    $makeGame($player);

    $this->actingAs($player)->get('/')
        ->assertInertia(fn (Assert $page) => $page->component('Game/NotReady'));

    $this->actingAs($player)->get('/play')->assertRedirect(route('game.intro'));
})->with([
    'no game' => fn () => fn (User $player) => null,
    'too few pictures' => fn () => fn (User $player) => Game::factory()->for($player)->has(Puzzle::factory()->count(8))->create(),
    'incomplete code' => fn () => fn (User $player) => Game::factory()->for($player)->ready()->create(['blik_code' => '12345']),
]);

test('the intro shows how far the player got', function () {
    $game = Game::factory()->ready()->create();
    $game->puzzles->first()->update(['solved_at' => now()]);

    $this->actingAs($game->user)->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Intro')
            ->where('solvedCount', 1)
            ->where('totalPuzzles', 9));
});

test('play sends only the current picture and the digits earned so far', function () {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);
    $puzzles = $game->puzzles;
    $puzzles[0]->update(['solved_at' => now()]);
    $puzzles[1]->update(['solved_at' => now()]);

    $response = $this->actingAs($game->user)->get('/play');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Game/Play')
        ->where('puzzle.id', $puzzles[2]->id)
        ->where('puzzle.number', 3)
        ->where('puzzle.imageUrl', $puzzles[2]->imageUrl())
        ->where('revealedDigits', ['9', '8'])
        ->where('advanceUrl', route('puzzles.solve', $puzzles[2]))
        ->where('preview', false)
        ->missing('code'));

    $response->assertDontSee('987654321');
    $response->assertDontSee($puzzles[3]->image_path);
});

test('opening the board starts the game clock once', function () {
    $game = Game::factory()->ready()->create();

    $this->freezeTime();
    $this->actingAs($game->user)->get('/play');
    $startedAt = $game->fresh()->started_at;

    $this->travel(5)->minutes();
    $this->actingAs($game->user)->get('/play');

    expect($startedAt)->not->toBeNull()
        ->and($game->fresh()->started_at->equalTo($startedAt))->toBeTrue();
});

test('solving the current puzzle reveals the next digit', function () {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);
    $puzzle = $game->puzzles->first();

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $puzzle), ['seconds' => 42])
        ->assertRedirect(route('game.play'));

    expect($puzzle->fresh())
        ->solved_at->not->toBeNull()
        ->solve_seconds->toBe(42)
        ->and($game->revealedDigits())->toBe(['9'])
        ->and($game->fresh()->completed_at)->toBeNull();
});

test('puzzles cannot be solved out of order', function () {
    $game = Game::factory()->ready()->create();
    $later = $game->puzzles[3];

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $later))
        ->assertStatus(422);

    expect($later->fresh()->solved_at)->toBeNull();
});

test('a puzzle cannot be solved twice', function () {
    $game = Game::factory()->ready()->create();
    $first = $game->puzzles->first();
    $first->update(['solved_at' => now()]);

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $first))
        ->assertStatus(422);

    expect($game->solvedPuzzlesCount())->toBe(1);
});

test('a player cannot solve a puzzle from someone else\'s game', function () {
    $game = Game::factory()->ready()->create();
    $otherGame = Game::factory()->ready()->create();
    $foreignPuzzle = $otherGame->puzzles->first();

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $foreignPuzzle))
        ->assertForbidden();

    expect($foreignPuzzle->fresh()->solved_at)->toBeNull();
});

test('the solve time must be a sensible number', function (mixed $seconds) {
    $game = Game::factory()->ready()->create();

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $game->puzzles->first()), ['seconds' => $seconds])
        ->assertSessionHasErrors('seconds');

    expect($game->solvedPuzzlesCount())->toBe(0);
})->with([-1, 'abc', 999_999_999]);

test('solving the last puzzle completes the game and opens the finale', function () {
    $game = Game::factory()->ready()->create();
    $game->puzzles->take(8)->each->update(['solved_at' => now()]);

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $game->puzzles->last()))
        ->assertRedirect(route('game.finale'));

    expect($game->fresh()->completed_at)->not->toBeNull();
});

test('the finale stays locked until the game is completed', function () {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);
    $game->puzzles->take(8)->each->update(['solved_at' => now()]);

    $this->actingAs($game->user)->get('/finale')
        ->assertRedirect(route('game.play'))
        ->assertDontSee('987654321');
});

test('the finale hands over the full code, the password and the wishes', function () {
    $game = Game::factory()->completed()->create([
        'blik_code' => '987654321',
        'blik_password' => 'tajne',
        'wishes' => '<p>Sto lat!</p><script>alert(1)</script>',
    ]);

    $this->actingAs($game->user)->get('/finale')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Finale')
            ->where('code', '987654321')
            ->where('password', 'tajne')
            ->where('wishes', '<p>Sto lat!</p>')
            ->where('preview', false));
});

test('a completed game skips straight to the finale', function (string $uri) {
    $game = Game::factory()->completed()->create();

    $this->actingAs($game->user)->get($uri)->assertRedirect(route('game.finale'));
})->with(['/', '/play']);

test('the blik code is encrypted in the database', function () {
    $game = Game::factory()->create(['blik_code' => '987654321', 'blik_password' => 'tajne']);

    $stored = $game->getConnection()->table('games')->find($game->id);

    expect($stored->blik_code)->not->toContain('987654321')
        ->and($stored->blik_password)->not->toContain('tajne')
        ->and($game->fresh()->blik_code)->toBe('987654321');
});
