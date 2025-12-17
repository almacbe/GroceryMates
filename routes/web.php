<?php

use App\Http\Controllers\MergeListsController;
use App\Http\Controllers\MergeSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/status', function () {
    if (! request()->hasHeader('X-Inertia')) {
        return view('live-check');
    }

    return inertia('LiveCheck');
})->name('status');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/merge', [MergeListsController::class, 'index'])->name('merge.index');
    Route::post('/merge', [MergeListsController::class, 'store'])->name('merge.store');
    Route::get('/merge/checklist', [MergeListsController::class, 'checklist'])->name('merge.checklist');

    Route::get('/merge/state', [MergeSessionController::class, 'getState'])->name('merge.state');
    Route::post('/merge/state', [MergeSessionController::class, 'updateList'])->name('merge.updateList');
    Route::delete('/merge/state', [MergeSessionController::class, 'clearState'])->name('merge.clearState');
});

require __DIR__.'/auth.php';
