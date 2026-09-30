<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Filament\Widgets\GameProgress;
use App\Models\Game;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Podgląd jako gracz')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (Game $record): string => route('preview.intro', $record))
                ->openUrlInNewTab()
                ->visible(fn (Game $record): bool => $record->puzzles()->exists()),
            Actions\ActionGroup::make([
                Actions\Action::make('showQrCode')
                    ->label('Pokaż kod QR')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('Kod QR do logowania gracza')
                    ->modalContent(fn (Game $record): View => view('filament.game-qr-code', [
                        'svg' => $record->loginQrSvg(),
                        'inkSvg' => $record->loginQrInkSvg(),
                        'png' => $record->loginQrPng(),
                        'url' => $record->loginUrl(),
                        'player' => $record->user?->name ?? 'gracz',
                        'telegramUrl' => route('preview.telegram', $record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zamknij'),
                Actions\Action::make('qrTelegram')
                    ->label('Telegram z kodem QR (do druku)')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Game $record): string => route('preview.telegram', $record))
                    ->openUrlInNewTab(),
                Actions\Action::make('regenerateQrCode')
                    ->label('Wygeneruj nowy kod QR')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Stary kod QR i link przestaną działać. Wydrukowany kod trzeba będzie wymienić.')
                    ->action(function (Game $record): void {
                        $record->regenerateLoginToken();

                        Notification::make()->title('Nowy kod QR gotowy')->success()->send();
                    }),
            ])
                ->label('Kod QR')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->button(),
            Actions\Action::make('resetProgress')
                ->label('Resetuj postęp')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Gracz zacznie od pierwszego obrazka. Obrazki, teksty i muzyka zostają bez zmian.')
                ->action(function (Game $record): void {
                    $record->resetProgress();

                    Notification::make()->title('Postęp zresetowany')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GameProgress::class,
        ];
    }
}
