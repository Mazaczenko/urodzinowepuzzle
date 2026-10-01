<?php

use App\Models\Game;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('an admin can preview any picture without touching the progress', function () {
    $game = Game::factory()->ready()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('preview.play', [$game, 3]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Play')
            ->where('puzzle.id', $game->puzzles[2]->id)
            ->where('puzzle.number', 3)
            ->where('puzzle.message', $game->puzzles[2]->message)
            ->where('solvedCount', 2)
            ->where('advanceUrl', route('preview.play', [$game, 4]))
            ->where('preview', true));

    expect($game->fresh())
        ->started_at->toBeNull()
        ->solvedPuzzlesCount()->toBe(0);
});

test('the preview starts at the first picture and ends at the finale', function () {
    $game = Game::factory()->ready()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('preview.play', $game))
        ->assertInertia(fn (Assert $page) => $page->where('puzzle.number', 1)->where('solvedCount', 0));

    $this->actingAs($admin)->get(route('preview.play', [$game, 6]))
        ->assertInertia(fn (Assert $page) => $page->where('advanceUrl', route('preview.finale', $game)));

    $this->actingAs($admin)->get(route('preview.play', [$game, 7]))->assertNotFound();
});

test('an admin can preview the finale of an unfinished game', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.finale', $game))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Finale')
            ->where('preview', true));

    expect($game->fresh()->completed_at)->toBeNull();
});

test('the player cannot use the preview to peek ahead', function (string $route) {
    $game = Game::factory()->ready()->create();

    $this->actingAs($game->user)->get(route($route, $game))->assertForbidden();
})->with(['preview.intro', 'preview.play', 'preview.finale']);

test('guests cannot open the preview', function () {
    $game = Game::factory()->ready()->create();

    $this->get(route('preview.play', $game))->assertRedirect(route('login'));
});

test('the admin menu links to playing the game', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertOk()
        ->assertSee('Ułóż puzzle')
        ->assertSee(route('preview.intro', $game), escape: false);
});

test('an admin can preview the intro screen, which leads into the preview', function () {
    $game = Game::factory()->ready()->create(['intro_text' => '<p>Zaczynamy!</p>']);

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.intro', $game))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Intro')
            ->where('introText', '<p>Zaczynamy!</p>')
            ->where('startUrl', route('preview.play', $game))
            ->where('preview', true));
});
