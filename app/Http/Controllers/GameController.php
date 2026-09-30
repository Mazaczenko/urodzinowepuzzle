<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Welcome the player; the button on this screen also unlocks audio playback.
     */
    public function intro(Request $request): Response|RedirectResponse
    {
        $game = $request->user()->game;

        if (! $game?->isReady()) {
            return Inertia::render('Game/NotReady');
        }

        if ($game->completed_at !== null) {
            return to_route('game.finale');
        }

        return Inertia::render('Game/Intro', [
            'solvedCount' => $game->solvedPuzzlesCount(),
            'totalPuzzles' => Game::PUZZLES_COUNT,
            'musicUrl' => $game->musicUrl(),
        ]);
    }

    /**
     * Show the puzzle the player is currently on.
     *
     * Only the current picture and the digits earned so far are sent to the
     * browser, so the rest of the code cannot be read from the page source.
     */
    public function play(Request $request): Response|RedirectResponse
    {
        $game = $request->user()->game;

        if (! $game?->isReady()) {
            return to_route('game.intro');
        }

        $puzzle = $game->currentPuzzle();

        if ($puzzle === null) {
            return to_route('game.finale');
        }

        if ($game->started_at === null) {
            $game->update(['started_at' => now()]);
        }

        $revealedDigits = $game->revealedDigits();

        return Inertia::render('Game/Play', [
            'puzzle' => [
                'id' => $puzzle->id,
                'number' => count($revealedDigits) + 1,
                'imageUrl' => $puzzle->imageUrl(),
                'caption' => $puzzle->caption,
            ],
            'revealedDigits' => $revealedDigits,
            'totalPuzzles' => Game::PUZZLES_COUNT,
            'musicUrl' => $game->musicUrl(),
            'advanceUrl' => route('puzzles.solve', $puzzle),
            'preview' => false,
        ]);
    }

    /**
     * The only place the full BLIK code leaves the server, and only after the last puzzle.
     */
    public function finale(Request $request): Response|RedirectResponse
    {
        $game = $request->user()->game;

        if ($game?->completed_at === null) {
            return to_route('game.play');
        }

        return Inertia::render('Game/Finale', [
            'code' => $game->blik_code,
            'password' => $game->blik_password,
            'wishes' => (string) str($game->wishes ?? '')->sanitizeHtml(),
            'musicUrl' => $game->finaleMusicUrl() ?? $game->musicUrl(),
            'preview' => false,
        ]);
    }
}
