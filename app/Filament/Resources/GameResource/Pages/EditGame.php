<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Filament\Widgets\GameProgress;
use App\Models\Game;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
