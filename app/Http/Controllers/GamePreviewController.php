<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Inertia\Inertia;
use Inertia\Response;

class GamePreviewController extends Controller
{
    /**
     * Let an admin play any picture of a game without touching the player's progress.
     */
    public function play(Game $game, int $position = 1): Response
    {
        $puzzles = $game->puzzles()->get();
        $puzzle = $puzzles->get($position - 1);

        abort_if($puzzle === null, 404);

        return Inertia::render('Game/Play', [
            'puzzle' => [
                'id' => $puzzle->id,
                'number' => $position,
                'imageUrl' => $puzzle->imageUrl(),
                'caption' => $puzzle->caption,
            ],
            'revealedDigits' => $game->digits($position - 1),
            'totalPuzzles' => $puzzles->count(),
            'musicUrl' => $game->musicUrl(),
            'advanceUrl' => $position < $puzzles->count()
                ? route('preview.play', [$game, $position + 1])
                : route('preview.finale', $game),
            'preview' => true,
        ]);
    }

    public function finale(Game $game): Response
    {
        return Inertia::render('Game/Finale', [
            'code' => (string) $game->blik_code,
            'password' => $game->blik_password,
            'wishes' => (string) str($game->wishes ?? '')->sanitizeHtml(),
            'musicUrl' => $game->finaleMusicUrl() ?? $game->musicUrl(),
            'preview' => true,
        ]);
    }
}
