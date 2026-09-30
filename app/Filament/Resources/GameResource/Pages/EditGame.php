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
                ->url(fn (Game $record): string => route('preview.play', $record))
                ->openUrlInNewTab()
                ->visible(fn (Game $record): bool => $record->puzzles()->exists()),
            Actions\Action::make('resetProgress')
                ->label('Resetuj postęp')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Gracz zacznie od pierwszego obrazka. Obrazki, kod i życzenia zostają bez zmian.')
                ->action(function (Game $record): void {
                    $record->resetProgress();

                    Notification::make()->title('Postęp zresetowany')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * The code and password are hidden from the model's array form, so they are added back for the admin.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['blik_code'] = $this->getRecord()->blik_code;
        $data['blik_password'] = $this->getRecord()->blik_password;

        return $data;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GameProgress::class,
        ];
    }
}
