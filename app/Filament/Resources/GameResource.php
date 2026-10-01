<?php

namespace App\Filament\Resources;

use App\Enums\Difficulty;
use App\Enums\GameTheme;
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
                Forms\Components\Section::make('Gracz')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Gracz')
                            ->relationship('user', 'name', fn (Builder $query) => $query->where('is_admin', false))
                            ->unique(ignoreRecord: true)
                            ->rule(Rule::exists('users', 'id')->where(fn (QueryBuilder $query) => $query->where('is_admin', false)))
                            ->validationMessages(['unique' => 'Ten gracz ma już swoją grę.'])
                            ->required(),
                        Forms\Components\TextInput::make('player_password')
                            ->label('Hasło gracza')
                            ->helperText('Zapisanie zmienia hasło konta gracza. Login i to hasło są wydrukowane na telegramie, na wypadek gdyby kod QR nie zadziałał.')
                            ->minLength(8)
                            ->maxLength(64)
                            ->suffixAction(Forms\Components\Actions\Action::make('generatePlayerPassword')
                                ->label('Wygeneruj')
                                ->icon('heroicon-o-sparkles')
                                ->action(fn (Forms\Set $set) => $set('player_password', Game::generatePlayerPassword()))),
                    ]),
                Forms\Components\Section::make('Trudność')
                    ->description('Na ile kawałków jest cięty każdy obrazek.')
                    ->schema([
                        Forms\Components\Radio::make('difficulty')
                            ->hiddenLabel()
                            ->options(Difficulty::class)
                            ->default(Difficulty::Growing)
                            ->required(),
                    ]),
                Forms\Components\Section::make('Wygląd')
                    ->description('Styl ekranów gry, od logowania po finał. W podglądzie można szybko porównać style bez zapisywania.')
                    ->schema([
                        Forms\Components\Radio::make('theme')
                            ->hiddenLabel()
                            ->options(GameTheme::class)
                            ->default(GameTheme::Fortis)
                            ->required(),
                    ]),
                Forms\Components\Section::make('Teksty')
                    ->description('Oba pojawiają się pisane litera po literze.')
                    ->schema([
                        static::textEditor('intro_text')
                            ->label('Tekst na początek')
                            ->helperText('Ekran startowy, zanim gracz zacznie układać, np. życzenia i zaproszenie do gry.'),
                        static::textEditor('finale_text')
                            ->label('Tekst na zakończenie')
                            ->helperText('Finał z fajerwerkami, po ułożeniu wszystkich obrazków.'),
                    ]),
                Forms\Components\Section::make('Muzyka')
                    ->columns(3)
                    ->schema([
                        static::musicUpload('music_path')
                            ->label('Muzyka w trakcie gry')
                            ->helperText('MP3 do 30 MB, gra w pętli przez wszystkie obrazki.'),
                        static::musicUpload('finale_music_path')
                            ->label('Muzyka na finał')
                            ->helperText('MP3 do 30 MB, płynnie zastępuje muzykę z gry po ułożeniu ostatniego obrazka, razem z fajerwerkami.'),
                        static::musicUpload('completion_sound_path')
                            ->label('Dźwięk po ułożeniu obrazka')
                            ->helperText('MP3 do 30 MB, gra po ułożeniu każdego obrazka oprócz ostatniego (po nim wchodzi muzyka na finał). Muzyka w tle na ten czas cichnie.'),
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
                    ->url(fn (Game $record): string => route('preview.intro', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Game $record): bool => $record->puzzles()->exists()),
                Tables\Actions\Action::make('resetProgress')
                    ->label('Resetuj postęp')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Gracz zacznie od pierwszego obrazka. Obrazki, teksty i muzyka zostają bez zmian.')
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
        $puzzlesCount = $game->puzzles()->count();

        if ($puzzlesCount !== Game::PUZZLES_COUNT) {
            $missing[] = 'obrazki ('.$puzzlesCount.' z '.Game::PUZZLES_COUNT.')';
        }

        $withoutMessage = $game->puzzlesWithoutMessageCount();

        if ($withoutMessage > 0) {
            $missing[] = 'teksty przy obrazkach ('.$withoutMessage.' bez tekstu)';
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

    protected static function textEditor(string $name): Forms\Components\RichEditor
    {
        return Forms\Components\RichEditor::make($name)
            ->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'h2', 'h3', 'bulletList', 'orderedList', 'redo', 'undo']);
    }

    protected static function musicUpload(string $name): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->disk('music')
            ->acceptedFileTypes(['audio/mpeg'])
            ->maxSize(30720);
    }
}
