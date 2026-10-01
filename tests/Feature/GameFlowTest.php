<?php

use App\Enums\GameTheme;
use App\Http\Middleware\HandleInertiaRequests;
use App\Mail\GameCompleted;
use App\Models\Game;
use App\Models\Puzzle;
use App\Models\User;
use Database\Seeders\GameContentSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login screen', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with(['/', '/play', '/finale']);

test('admins are sent to the panel instead of the game', function (string $uri) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get($uri)->assertRedirect('/admin');
})->with(['/', '/play', '/finale']);

test('an admin logging in through the game is taken out of the single page app', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())])
        ->get('/')
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', '/admin');
});

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
    'picture without a message' => fn () => fn (User $player) => Game::factory()->for($player)->ready()->create()->puzzles->first()->update(['message' => null]),
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

test('play sends only the current picture and its message', function () {
    $game = Game::factory()->ready()->create();
    $puzzles = $game->puzzles;
    $puzzles[0]->update(['solved_at' => now()]);
    $puzzles[1]->update(['solved_at' => now()]);

    $response = $this->actingAs($game->user)->get('/play');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Game/Play')
        ->where('puzzle.id', $puzzles[2]->id)
        ->where('puzzle.number', 3)
        ->where('puzzle.imageUrl', $puzzles[2]->imageUrl())
        ->where('puzzle.message', $puzzles[2]->message)
        ->where('puzzle.leadMessage', $puzzles[2]->lead_message)
        ->where('puzzle.grid', ['columns' => 5, 'rows' => 2])
        ->where('puzzle.hint', 0.16)
        ->where('solvedCount', 2)
        ->where('advanceUrl', route('puzzles.solve', $puzzles[2]))
        ->where('preview', false));

    $response->assertDontSee($puzzles[3]->image_path);
    $response->assertDontSee(e($puzzles[3]->message));
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

test('solving the current puzzle moves on to the next one', function () {
    $game = Game::factory()->ready()->create();
    $puzzle = $game->puzzles->first();

    $this->actingAs($game->user)
        ->post(route('puzzles.solve', $puzzle), ['seconds' => 42])
        ->assertRedirect(route('game.play'));

    expect($puzzle->fresh())
        ->solved_at->not->toBeNull()
        ->solve_seconds->toBe(42)
        ->and($game->currentPuzzle()->is($game->puzzles[1]))->toBeTrue()
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
    $game = Game::factory()->ready()->create();
    $game->puzzles->take(8)->each->update(['solved_at' => now()]);

    $this->actingAs($game->user)->get('/finale')->assertRedirect(route('game.play'));
});

test('the finale shows the sanitized closing text', function () {
    $game = Game::factory()->completed()->create([
        'finale_text' => '<p>Sto lat!</p><script>alert(1)</script>',
    ]);

    $this->actingAs($game->user)->get('/finale')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Finale')
            ->where('finaleText', '<p>Sto lat!</p>')
            ->where('pictureUrl', $game->puzzles->last()->imageUrl())
            ->where('preview', false));
});

test('a completed game skips straight to the finale', function (string $uri) {
    $game = Game::factory()->completed()->create();

    $this->actingAs($game->user)->get($uri)->assertRedirect(route('game.finale'));
})->with(['/', '/play']);

test('finishing the game e-mails the organiser, and only then', function () {
    Mail::fake();
    config(['app.game_completed_recipients' => ['m.piorkowska@fortis.pl', 'w.mazur@fortis.pl']]);

    $game = Game::factory()->ready()->create();
    $game->puzzles->take(7)->each->update(['solved_at' => now()]);

    $this->actingAs($game->user)->post(route('puzzles.solve', $game->puzzles[7]));
    Mail::assertNothingSent();

    $this->actingAs($game->user)->post(route('puzzles.solve', $game->puzzles[8]));

    Mail::assertSent(GameCompleted::class, fn (GameCompleted $mail) => $mail->hasTo('m.piorkowska@fortis.pl')
        && $mail->hasTo('w.mazur@fortis.pl')
        && $mail->game->is($game));
    Mail::assertSentCount(1);
});

test('the completion e-mail says Mirek has put the puzzles together', function () {
    $game = Game::factory()->completed()->create();

    (new GameCompleted($game))
        ->assertHasSubject('Mirek ułożył puzzle !')
        ->assertSeeInHtml('Mirek ułożył puzzle !');
});

test('the birthday wishes open the game on the intro screen', function () {
    $game = Game::factory()->ready()->create(['intro_text' => GameContentSeeder::INTRO_TEXT.'<script>alert(1)</script>']);

    $this->actingAs($game->user)->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Intro')
            ->where('introText', GameContentSeeder::INTRO_TEXT)
            ->where('startUrl', route('game.play'))
            ->where('preview', false));
});

test('one track plays through the pictures and another one on the finale', function () {
    $game = Game::factory()->ready()->create(['music_path' => 'gra.mp3', 'finale_music_path' => 'final.mp3']);

    $this->actingAs($game->user)->get('/play')
        ->assertInertia(fn (Assert $page) => $page->where('musicUrl', asset('music/gra.mp3')));

    $game->puzzles->each->update(['solved_at' => now()]);
    $game->update(['completed_at' => now()]);

    $this->actingAs($game->user->fresh())->get('/finale')
        ->assertInertia(fn (Assert $page) => $page->where('musicUrl', asset('music/final.mp3')));
});

test('the finale keeps the game music when it has no track of its own', function () {
    $game = Game::factory()->completed()->create(['music_path' => 'gra.mp3']);

    $this->actingAs($game->user)->get('/finale')
        ->assertInertia(fn (Assert $page) => $page->where('musicUrl', asset('music/gra.mp3')));
});

test('the board gets the shared sound played after each picture', function () {
    $game = Game::factory()->ready()->create(['completion_sound_path' => 'brawo.mp3']);

    $this->actingAs($game->user)->get('/play')
        ->assertInertia(fn (Assert $page) => $page->where('completionSoundUrl', asset('music/brawo.mp3')));

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.play', [$game, 2]))
        ->assertInertia(fn (Assert $page) => $page->where('completionSoundUrl', asset('music/brawo.mp3')));
});

test('every screen, the login included, uses the look chosen for the game', function () {
    $game = Game::factory()->ready()->create(['theme' => GameTheme::Fortis]);

    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('theme', 'fortis'));
    $this->actingAs($game->user)->get('/')->assertInertia(fn (Assert $page) => $page->where('theme', 'fortis'));
    $this->actingAs($game->user)->get('/play')->assertInertia(fn (Assert $page) => $page->where('theme', 'fortis'));
});

test('the preview shows the look of the previewed game', function () {
    Game::factory()->ready()->create(['theme' => GameTheme::Fortis]);
    $game = Game::factory()->ready()->create(['theme' => GameTheme::Checkers]);

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.intro', $game))
        ->assertInertia(fn (Assert $page) => $page->where('theme', 'checkers'));
});

test('later pictures are cut into more pieces', function () {
    $game = Game::factory()->ready()->create();
    $game->puzzles->take(6)->each->update(['solved_at' => now()]);

    $this->actingAs($game->user)->get('/play')
        ->assertInertia(fn (Assert $page) => $page->where('puzzle.number', 7)->where('puzzle.grid', ['columns' => 6, 'rows' => 3])->where('puzzle.hint', 0));
});
