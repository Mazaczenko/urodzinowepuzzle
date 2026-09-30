<?php

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Game;
use App\Models\Puzzle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PuzzlesRelationManager extends RelationManager
{
    protected static string $relationship = 'puzzles';

    protected static ?string $title = 'Obrazki';

    protected static ?string $modelLabel = 'obrazek';

    protected static ?string $pluralModelLabel = 'obrazki';

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('Obrazek')
                    ->helperText('Zostanie przycięty do proporcji 5:2 i zapisany jako WebP.')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['5:2'])
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('5:2')
                    ->imageResizeTargetWidth((string) Puzzle::IMAGE_WIDTH)
                    ->imageResizeTargetHeight((string) Puzzle::IMAGE_HEIGHT)
                    ->disk('public')
                    ->directory('puzzles')
                    ->maxSize(12288)
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => Puzzle::storeImage($file))
                    ->required(),
                Forms\Components\TextInput::make('caption')
                    ->label('Podpis')
                    ->helperText('Pokaże się po ułożeniu obrazka, np. „Nasz pierwszy wyjazd ❤️”.')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->defaultSort('position')
            ->reorderable('position')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#'),
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Obrazek')
                    ->disk('public')
                    ->width(200)
                    ->height(80),
                Tables\Columns\TextColumn::make('caption')
                    ->label('Podpis')
                    ->placeholder('—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('solved_at')
                    ->label('Ułożony')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->puzzles()->count() < Game::PUZZLES_COUNT),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * Filament renumbers rows in a single UPDATE, which trips the unique
     * (game_id, position) index, so the game reorders its puzzles itself.
     *
     * @param  array<int|string>  $order
     */
    public function reorderTable(array $order): void
    {
        if (! $this->getTable()->isReorderable()) {
            return;
        }

        $this->getOwnerRecord()->reorderPuzzles(array_values($order));
    }
}
