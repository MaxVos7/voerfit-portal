<?php

use App\Http\Controllers\CowOverviewController;
use App\Http\Controllers\DayOverviewController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// TODO: move behind the auth middleware once customer login and farm scoping exist.
Route::get('dagoverzicht', DayOverviewController::class)->name('day-overview');
Route::get('koeien/{animal}', CowOverviewController::class)->whereNumber('animal')->name('cows.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
