<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Contracts\View\View;
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
                'title' => $puzzle->title,
                'leadMessage' => $puzzle->lead_message,
                'message' => $puzzle->message,
                'grid' => $game->difficulty->grid($position),
                'hint' => $game->difficulty->hint($position),
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
            'pictureUrl' => $game->puzzles()->get()->last()?->imageUrl(),
            'musicUrl' => $game->finaleMusicUrl() ?? $game->musicUrl(),
            'preview' => true,
        ]);
    }

    /**
     * The QR code on a PRL-style telegram form, sized to print on A5, with the login and password
     * in case the code does not scan.
     */
    public function telegram(Game $game): View
    {
        $message = 'WSZYSTKIEGO NAJLEPSZEGO STOP ZESKANUJ KOD OBOK STOP CZEKA NA CIEBIE NIESPODZIANKA STOP UŁÓŻ WSZYSTKO DO KOŃCA STOP';

        return view('telegram', [
            'qr' => $game->loginQrInkSvg(),
            'addressee' => mb_strtoupper($game->user?->name ?? 'SOLENIZANT'),
            'message' => $message,
            'words' => str_word_count(str_replace('STOP', '', $message), 0, 'ĄĆĘŁŃÓŚŹŻ'),
            'date' => now()->format('d.m.Y'),
            'site' => parse_url(config('app.player_url'), PHP_URL_HOST),
            'login' => $game->user?->email,
            'password' => $game->player_password,
        ]);
    }
}
