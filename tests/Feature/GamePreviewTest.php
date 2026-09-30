<?php

use App\Models\Game;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('an admin can preview any picture without touching the progress', function () {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('preview.play', [$game, 3]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Play')
            ->where('puzzle.id', $game->puzzles[2]->id)
            ->where('puzzle.number', 3)
            ->where('revealedDigits', ['9', '8'])
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
        ->assertInertia(fn (Assert $page) => $page->where('puzzle.number', 1)->where('revealedDigits', []));

    $this->actingAs($admin)->get(route('preview.play', [$game, 9]))
        ->assertInertia(fn (Assert $page) => $page->where('advanceUrl', route('preview.finale', $game)));

    $this->actingAs($admin)->get(route('preview.play', [$game, 10]))->assertNotFound();
});

test('an admin can preview the finale of an unfinished game', function () {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.finale', $game))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game/Finale')
            ->where('code', '987654321')
            ->where('preview', true));

    expect($game->fresh()->completed_at)->toBeNull();
});

test('the player cannot use the preview to peek at the code', function (string $route) {
    $game = Game::factory()->ready()->create(['blik_code' => '987654321']);

    $this->actingAs($game->user)->get(route($route, $game))
        ->assertForbidden()
        ->assertDontSee('987654321');
})->with(['preview.play', 'preview.finale']);

test('guests cannot open the preview', function () {
    $game = Game::factory()->ready()->create();

    $this->get(route('preview.play', $game))->assertRedirect(route('login'));
});
