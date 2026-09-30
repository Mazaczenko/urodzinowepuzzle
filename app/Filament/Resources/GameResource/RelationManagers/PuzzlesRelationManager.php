<?php

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Game;
use App\Models\Puzzle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PuzzlesRelationManager extends RelationManager
{
    protected static string $relationship = 'puzzles';

    protected static ?string $title = 'Obrazki';

    protected static ?string $modelLabel = 'obrazek';

    protected static ?string $pluralModelLabel = 'obrazki';

    /**
     * Set by "Zapisz i edytuj następny", so the next picture opens once the current one is saved.
     */
    protected ?Puzzle $puzzleToEditNext = null;

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
                    ->imagePreviewHeight('260')
                    ->disk('puzzles')
                    ->maxSize(12288)
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => Puzzle::storeImage($file))
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->label('Tekst')
                    ->helperText('Pokaże się graczowi po ułożeniu tego obrazka. Można zostawić puste i uzupełnić później, także seederem GameContentSeeder.')
                    ->rows(5)
                    ->autosize()
                    ->maxLength(2000),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Puzzle $record): string => 'obrazek '.$record->position)
            ->defaultSort('position')
            ->reorderable('position')
            ->reorderRecordsTriggerAction(fn (Tables\Actions\Action $action, bool $isReordering): Tables\Actions\Action => $action
                ->button()
                ->label($isReordering ? 'Zakończ zmianę kolejności' : 'Zmień kolejność (przeciągnij)'))
            ->paginated(false)
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->recordAction('edit')
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image_path')
                        ->label('Obrazek')
                        ->disk('puzzles')
                        ->height(140)
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'rounded-lg object-cover', 'style' => 'aspect-ratio: 5 / 2; width: 100%; height: auto;']),
                    Tables\Columns\TextColumn::make('position')
                        ->formatStateUsing(fn (int $state): string => 'Obrazek '.$state)
                        ->weight('bold'),
                    Tables\Columns\TextColumn::make('message')
                        ->placeholder('Brak tekstu')
                        ->limit(160)
                        ->wrap()
                        ->color('gray'),
                    Tables\Columns\TextColumn::make('solved_at')
                        ->formatStateUsing(fn ($state): string => 'Ułożony '.$state->format('d.m.Y H:i'))
                        ->badge()
                        ->color('success'),
                ])->space(2),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->visible(fn (): bool => $this->getOwnerRecord()->puzzles()->count() < Game::PUZZLES_COUNT),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading(fn (Puzzle $record): string => 'Obrazek '.$record->position.' z '.Game::PUZZLES_COUNT)
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->extraModalFooterActions(fn (Tables\Actions\EditAction $action, Puzzle $record): array => $this->nextPuzzle($record)
                        ? [$action->makeModalSubmitAction('saveAndEditNext', arguments: ['next' => true])->label('Zapisz i edytuj następny')]
                        : [])
                    ->after(function (Puzzle $record, array $arguments): void {
                        if (($arguments['next'] ?? false) && ($next = $this->nextPuzzle($record))) {
                            $this->puzzleToEditNext = $next;
                        }
                    }),
                Tables\Actions\Action::make('moveEarlier')
                    ->label('Wcześniej')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn (Puzzle $record): bool => $record->position > 1)
                    ->action(fn (Puzzle $record) => $this->move($record, -1)),
                Tables\Actions\Action::make('moveLater')
                    ->label('Później')
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn (Puzzle $record): bool => $record->position < $this->getOwnerRecord()->puzzles()->count())
                    ->action(fn (Puzzle $record) => $this->move($record, 1)),
                Tables\Actions\Action::make('preview')
                    ->label('Zagraj')
                    ->icon('heroicon-o-play')
                    ->color('gray')
                    ->url(fn (Puzzle $record): string => route('preview.play', [$this->getOwnerRecord(), $record->position]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * Filament closes the modal after the action runs, so the next picture is opened afterwards.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function callMountedTableAction(array $arguments = []): mixed
    {
        $result = parent::callMountedTableAction($arguments);

        if ($this->puzzleToEditNext !== null) {
            $this->mountTableAction('edit', (string) $this->puzzleToEditNext->getKey());
            $this->puzzleToEditNext = null;
        }

        return $result;
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

    /**
     * Swap a picture with its neighbour: one place earlier (-1) or later (1).
     */
    protected function move(Puzzle $puzzle, int $step): void
    {
        $ids = $this->getOwnerRecord()->puzzles()->pluck('id')->all();
        $from = array_search($puzzle->id, $ids, true);
        $to = $from + $step;

        if ($from === false || ! isset($ids[$to])) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

        $this->getOwnerRecord()->reorderPuzzles($ids);
    }

    protected function nextPuzzle(Puzzle $puzzle): ?Puzzle
    {
        return $this->getOwnerRecord()->puzzles()->where('position', '>', $puzzle->position)->first();
    }
}
