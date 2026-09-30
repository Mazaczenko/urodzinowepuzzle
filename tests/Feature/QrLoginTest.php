<?php

use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Models\Game;
use App\Models\User;
use Livewire\Livewire;

test('the link from the qr code logs the player straight in', function () {
    $game = Game::factory()->ready()->create();

    $this->get($game->loginUrl())->assertRedirect(route('game.intro'));

    $this->assertAuthenticatedAs($game->user);
});

test('the qr code logs the player in even when someone else was logged in on the device', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get($game->loginUrl())
        ->assertRedirect(route('game.intro'));

    $this->assertAuthenticatedAs($game->user);
});

test('a wrong or replaced token does not log anyone in', function () {
    $game = Game::factory()->ready()->create();
    $oldUrl = $game->loginUrl();

    $game->regenerateLoginToken();

    $this->get($oldUrl)->assertNotFound();
    $this->get('/wejdz/'.str_repeat('a', 40))->assertNotFound();
    $this->assertGuest();

    expect($game->loginUrl())->not->toBe($oldUrl);
});

test('the login token never reaches the game screens', function () {
    $game = Game::factory()->ready()->create();
    $game->loginUrl();

    $this->actingAs($game->user)->get('/')->assertDontSee($game->fresh()->login_token);
});

test('an admin can show and replace the qr code', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->mountAction('showQrCode')
        ->assertSee($game->fresh()->loginUrl())
        ->assertSee('Pobierz PNG');

    $oldUrl = $game->fresh()->loginUrl();

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->callAction('regenerateQrCode');

    expect($game->fresh()->loginUrl())->not->toBe($oldUrl);
});

test('an admin can open the qr code printed on a telegram', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.telegram', $game))
        ->assertOk()
        ->assertSee('TELEGRAM')
        ->assertSee('OB. '.mb_strtoupper($game->user->name))
        ->assertSee('<svg', escape: false);
});

test('the player cannot open the telegram', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs($game->user)->get(route('preview.telegram', $game))->assertForbidden();
});
