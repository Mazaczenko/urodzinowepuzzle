<?php

namespace App\Http\Controllers;

use App\Mail\GameCompleted;
use App\Models\Puzzle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PuzzleController extends Controller
{
    /**
     * Mark the player's current puzzle as solved and move on to the next one.
     */
    public function solve(Request $request, Puzzle $puzzle): RedirectResponse
    {
        $game = $request->user()->game;

        abort_unless($game !== null && $puzzle->game_id === $game->id, 403);
        abort_unless((bool) $game->currentPuzzle()?->is($puzzle), 422);

        $validated = $request->validate([
            'seconds' => ['nullable', 'integer', 'min:0', 'max:604800'],
        ]);

        $puzzle->update([
            'solved_at' => now(),
            'solve_seconds' => $validated['seconds'] ?? null,
        ]);

        if ($game->currentPuzzle() === null) {
            $game->update(['completed_at' => now()]);

            // Sent straight away, but a failing mail server must never keep the player from the finale.
            try {
                Mail::to(config('app.game_completed_recipients'))->send(new GameCompleted($game));
            } catch (Throwable $exception) {
                report($exception);
            }

            return to_route('game.finale');
        }

        return to_route('game.play');
    }
}
