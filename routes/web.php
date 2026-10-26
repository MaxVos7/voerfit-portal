<?php

use App\Http\Controllers\CameraImagesController;
use App\Http\Controllers\CowOverviewController;
use App\Http\Controllers\DayOverviewColumnsController;
use App\Http\Controllers\DayOverviewController;
use App\Http\Controllers\FeedRulesController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dagoverzicht')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dagoverzicht', DayOverviewController::class)->name('day-overview');
    Route::put('dagoverzicht/kolommen', [DayOverviewColumnsController::class, 'update'])->name('day-overview.columns.update');
    Route::delete('dagoverzicht/kolommen', [DayOverviewColumnsController::class, 'destroy'])->name('day-overview.columns.destroy');
    Route::get('koeien/{animal}', CowOverviewController::class)->whereNumber('animal')->name('cows.show');
    Route::get('koeien/{animal}/camerabeelden', CameraImagesController::class)->whereNumber('animal')->name('cows.camera');
    Route::get('voerregels', FeedRulesController::class)->name('feed-rules');

    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
