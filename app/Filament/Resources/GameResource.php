<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Filament\Resources\GameResource\RelationManagers;
use App\Filament\Widgets\GameProgress;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $modelLabel = 'gra';

    protected static ?string $pluralModelLabel = 'gry';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gracz i czek BLIK')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Gracz')
                            ->relationship('user', 'name', fn (Builder $query) => $query->where('is_admin', false))
                            ->unique(ignoreRecord: true)
                            ->rule(Rule::exists('users', 'id')->where(fn (QueryBuilder $query) => $query->where('is_admin', false)))
                            ->validationMessages(['unique' => 'Ten gracz ma już swoją grę.'])
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('blik_code')
                            ->label('Kod czeku BLIK')
                            ->helperText('Dokładnie '.Game::PUZZLES_COUNT.' cyfr. Sprawdź w aplikacji banku, czy czek będzie ważny w dniu urodzin.')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->inputMode('numeric')
                            ->length(Game::PUZZLES_COUNT)
                            ->rule('digits:'.Game::PUZZLES_COUNT),
                        Forms\Components\TextInput::make('blik_password')
                            ->label('Hasło czeku')
                            ->helperText('Opcjonalne, jeśli bank wymaga hasła przy wypłacie.')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(50),
                    ]),
                Forms\Components\Section::make('Życzenia')
                    ->description('Pojawią się w finale, pisane litera po literze.')
                    ->schema([
                        Forms\Components\RichEditor::make('wishes')
                            ->hiddenLabel()
                            ->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'h2', 'h3', 'bulletList', 'orderedList', 'redo', 'undo']),
                    ]),
                Forms\Components\Section::make('Muzyka')
                    ->columns(2)
                    ->schema([
                        static::musicUpload('music_path')
                            ->label('Muzyka w trakcie gry')
                            ->helperText('MP3, gra w pętli podczas układania.'),
                        static::musicUpload('finale_music_path')
                            ->label('Muzyka na finał')
                            ->helperText('MP3, włącza się razem z fajerwerkami.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Gracz')
                    ->description(fn (Game $record): ?string => $record->user?->email),
                Tables\Columns\TextColumn::make('readiness')
                    ->label('Gotowość')
                    ->badge()
                    ->state(fn (Game $record): string => $record->isReady() ? 'Gotowa' : 'Niekompletna')
                    ->color(fn (string $state): string => $state === 'Gotowa' ? 'success' : 'warning')
                    ->tooltip(fn (Game $record): ?string => static::missingParts($record)),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Postęp')
                    ->state(fn (Game $record): string => $record->solvedPuzzlesCount().' / '.Game::PUZZLES_COUNT),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Rozpoczęta')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Ukończona')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Podgląd jako gracz')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Game $record): string => route('preview.play', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Game $record): bool => $record->puzzles()->exists()),
                Tables\Actions\Action::make('resetProgress')
                    ->label('Resetuj postęp')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Gracz zacznie od pierwszego obrazka. Obrazki, kod i życzenia zostają bez zmian.')
                    ->action(fn (Game $record) => $record->resetProgress()),
                Tables\Actions\EditAction::make(),
            ]);
    }

    /**
     * Explain what still has to be filled in before the game can be played.
     */
    public static function missingParts(Game $game): ?string
    {
        $missing = [];

        if (! $game->hasCompleteCode()) {
            $missing[] = 'kod BLIK ('.Game::PUZZLES_COUNT.' cyfr)';
        }

        $puzzlesCount = $game->puzzles()->count();

        if ($puzzlesCount !== Game::PUZZLES_COUNT) {
            $missing[] = 'obrazki ('.$puzzlesCount.' z '.Game::PUZZLES_COUNT.')';
        }

        return $missing === [] ? null : 'Brakuje: '.implode(', ', $missing);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PuzzlesRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            GameProgress::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }

    protected static function musicUpload(string $name): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->disk('public')
            ->directory('music')
            ->acceptedFileTypes(['audio/mpeg'])
            ->maxSize(12288);
    }
}
