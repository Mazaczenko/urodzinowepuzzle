<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Inertia\Inertia;
use Inertia\Response;

class GamePreviewController extends Controller
{
    /**
     * The intro screen as the player sees it at the start, leading into the preview.
     */
    public function intro(Game $game): Response
    {
        return Inertia::render('Game/Intro', [
            'introText' => $game->introHtml(),
            'solvedCount' => 0,
            'totalPuzzles' => Game::PUZZLES_COUNT,
            'musicUrl' => $game->musicUrl(),
            'startUrl' => route('preview.play', $game),
            'preview' => true,
        ]);
    }

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
                'message' => $puzzle->message,
            ],
            'solvedCount' => $position - 1,
            'totalPuzzles' => $puzzles->count(),
            'musicUrl' => $game->musicUrl(),
            'completionSoundUrl' => $game->completionSoundUrl(),
            'advanceUrl' => $position < $puzzles->count()
                ? route('preview.play', [$game, $position + 1])
                : route('preview.finale', $game),
            'preview' => true,
        ]);
    }

    public function finale(Game $game): Response
    {
        return Inertia::render('Game/Finale', [
            'finaleText' => $game->finaleHtml(),
            'musicUrl' => $game->finaleMusicUrl() ?? $game->musicUrl(),
            'preview' => true,
        ]);
    }
}
