<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tom\Services\ScreensController;

Route::get('/', fn () => redirect(route('dashboard')));

Route::get('/screen/{screen}', [ScreensController::class, 'show'])
    ->whereUuid('screen')
    ->name('screen.show');

require __DIR__.'/auth.php';
