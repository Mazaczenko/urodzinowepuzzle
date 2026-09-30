<?php

namespace App\Providers\Filament;

use App\Models\Game;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Urodzinowe Puzzle')
            ->favicon(asset('favicon/favicon-32x32.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->navigationItems([
                // Goes through the preview, so admins can play without touching the player's progress.
                NavigationItem::make('Ułóż puzzle')
                    ->icon('heroicon-o-play')
                    ->url(fn (): ?string => ($game = static::playableGame()) ? route('preview.intro', $game) : null, shouldOpenInNewTab: true)
                    ->visible(fn (): bool => static::playableGame() !== null)
                    ->sort(-1),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Ułóż puzzle')
                    ->icon('heroicon-o-play')
                    ->url(fn (): ?string => ($game = static::playableGame()) ? route('preview.intro', $game) : null, shouldOpenInNewTab: true)
                    ->visible(fn (): bool => static::playableGame() !== null),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected static function playableGame(): ?Game
    {
        return once(fn () => Game::query()->has('puzzles')->oldest('id')->first());
    }
}
