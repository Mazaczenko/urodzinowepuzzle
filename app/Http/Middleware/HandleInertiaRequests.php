<?php

namespace App\Http\Middleware;

use App\Enums\GameTheme;
use App\Models\Game;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Never the whole model: its loaded relations would carry the game, and with it every picture's message.
                'user' => $request->user()?->only('id', 'name', 'email', 'email_verified_at'),
            ],
            'theme' => fn (): string => $this->theme($request)->value,
        ];
    }

    /**
     * The previewed game's look, else the player's, else the one game's (the login screen has no user yet).
     */
    protected function theme(Request $request): GameTheme
    {
        $game = $request->route('game');

        if (! $game instanceof Game) {
            $game = $request->user()?->game ?? Game::query()->oldest('id')->first();
        }

        return $game->theme ?? GameTheme::Classic;
    }
}
