<?php

use App\Filament\Resources\GameResource\Pages\EditGame;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('the link from the qr code logs the player in and opens the puzzles', function () {
    $game = Game::factory()->ready()->create();

    $this->get($game->loginUrl())->assertRedirect(route('game.play'));

    $this->assertAuthenticatedAs($game->user);
});

test('the qr code logs the player in even when someone else was logged in on the device', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get($game->loginUrl())
        ->assertRedirect(route('game.play'));

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

test('the telegram shows the login and the player password as typed', function () {
    $game = Game::factory()->ready()->create(['player_password' => 'rower-Ab3xyz']);

    $this->actingAs(User::factory()->admin()->create())->get(route('preview.telegram', $game))
        ->assertOk()
        ->assertSee('LUB WEJDŹ NA URODZINOWEPUZZLE.PL STOP')
        ->assertSee($game->user->email)
        ->assertSee('rower-Ab3xyz');
});

test('setting the player password changes the password the player logs in with', function () {
    $game = Game::factory()->ready()->create();

    $game->update(['player_password' => 'rower-new123']);

    expect(Hash::check('rower-new123', $game->user->fresh()->password))->toBeTrue();

    $this->post('/login', ['email' => $game->user->email, 'password' => 'rower-new123']);

    $this->assertAuthenticatedAs($game->user);
});

test('a generated player password has no look-alike characters', function () {
    expect(Game::generatePlayerPassword())->toMatch('/^rower-[a-hjkmnp-z2-9]{6}$/');
});

test('the player cannot open the telegram', function () {
    $game = Game::factory()->ready()->create();

    $this->actingAs($game->user)->get(route('preview.telegram', $game))->assertForbidden();
});

test('the admin form shows the saved player password and keeps it on save', function () {
    $game = Game::factory()->ready()->create(['player_password' => 'rower-keep12']);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(EditGame::class, ['record' => $game->getRouteKey()])
        ->assertFormSet(['player_password' => 'rower-keep12'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($game->fresh()->player_password)->toBe('rower-keep12');
});
