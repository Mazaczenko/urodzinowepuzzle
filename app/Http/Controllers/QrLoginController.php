<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrLoginController extends Controller
{
    /**
     * Log the player in from the link in the QR code, whoever was logged in on this device before,
     * and take them straight to the puzzles.
     */
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $game = Game::query()->where('login_token', $token)->first();

        abort_if($game?->user === null || $game->user->is_admin, 404);

        Auth::guard('web')->logout();
        $request->session()->invalidate();

        Auth::guard('web')->login($game->user, remember: true);
        $request->session()->regenerate();

        return to_route('game.play');
    }
}
