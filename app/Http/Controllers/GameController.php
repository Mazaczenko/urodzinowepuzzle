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
            'introText' => $game->introHtml(),
            'solvedCount' => $game->solvedPuzzlesCount(),
            'totalPuzzles' => Game::PUZZLES_COUNT,
            'musicUrl' => $game->musicUrl(),
            'startUrl' => route('game.play'),
            'preview' => false,
        ]);
    }

    /**
     * Show the puzzle the player is currently on.
     *
     * Only the current picture and its message are sent to the browser,
     * so the following ones cannot be read from the page source.
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

        $solvedCount = $game->solvedPuzzlesCount();

        return Inertia::render('Game/Play', [
            'puzzle' => [
                'id' => $puzzle->id,
                'number' => $solvedCount + 1,
                'imageUrl' => $puzzle->imageUrl(),
                'leadMessage' => $puzzle->lead_message,
                'message' => $puzzle->message,
                'grid' => $game->difficulty->grid($solvedCount + 1),
                'hint' => $game->difficulty->hint($solvedCount + 1),
            ],
            'solvedCount' => $solvedCount,
            'totalPuzzles' => Game::PUZZLES_COUNT,
            'musicUrl' => $game->musicUrl(),
            'completionSoundUrl' => $game->completionSoundUrl(),
            'advanceUrl' => route('puzzles.solve', $puzzle),
            'preview' => false,
        ]);
    }

    /**
     * Fireworks and the closing text, once the last picture is put together.
     */
    public function finale(Request $request): Response|RedirectResponse
    {
        $game = $request->user()->game;

        if ($game?->completed_at === null) {
            return to_route('game.play');
        }

        return Inertia::render('Game/Finale', [
            'finaleText' => $game->finaleHtml(),
            'pictureUrl' => $game->puzzles()->get()->last()?->imageUrl(),
            'musicUrl' => $game->finaleMusicUrl() ?? $game->musicUrl(),
            'preview' => false,
        ]);
    }
}
