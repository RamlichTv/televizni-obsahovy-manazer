<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(route('dashboard')));

require __DIR__.'/auth.php';
