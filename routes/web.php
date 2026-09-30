<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\GamePreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PuzzleController;
use App\Http\Controllers\QrLoginController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectAdminToPanel;
use Illuminate\Support\Facades\Route;

Route::get('/wejdz/{token}', QrLoginController::class)
    ->middleware('throttle:10,1')
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('game.qr-login');

Route::middleware(['auth', RedirectAdminToPanel::class])->group(function () {
    Route::get('/', [GameController::class, 'intro'])->name('game.intro');
    Route::get('/play', [GameController::class, 'play'])->name('game.play');
    Route::post('/puzzles/{puzzle}/solve', [PuzzleController::class, 'solve'])->name('puzzles.solve');
    Route::get('/finale', [GameController::class, 'finale'])->name('game.finale');
});

Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('preview/{game}')->name('preview.')->group(function () {
    Route::get('/', [GamePreviewController::class, 'intro'])->name('intro');
    Route::get('/play/{position?}', [GamePreviewController::class, 'play'])->whereNumber('position')->name('play');
    Route::get('/finale', [GamePreviewController::class, 'finale'])->name('finale');
    Route::get('/telegram', [GamePreviewController::class, 'telegram'])->name('telegram');
});

Route::redirect('/dashboard', '/')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
