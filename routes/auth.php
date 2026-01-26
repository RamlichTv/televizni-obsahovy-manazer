<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Tom\Content\TagsController;
use App\Http\Controllers\Tom\Content\ContentTypeController;
use App\Http\Controllers\Tom\ContentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    /* ## NORMAL USER ## */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    /* ## CONTENT ROUTES ## */
    Route::name('content.')->prefix('content')
        ->group(function () {

        // ## Viewing
        Route::get('/', [ContentController::class, 'index'])
            ->middleware('perms:publish_content,edit_content,view_content,delete_content,manage_archived,manage_content_types,manage_tags')
            ->name('index');
        Route::get('/{content_object}', [ContentController::class, 'show'])
            ->middleware('can:view,content_object')
            ->name('show');

        // ## Creating
        Route::middleware('can:create_content')->group(function() {
            Route::get('/create', [ContentController::class, 'create'])->name('create');
            Route::post('/', [ContentController::class, 'store'])->name('store');
        });

        // ## Editing
        Route::middleware('can:update,content_object')->group(function() {
            Route::get('/{content_object}/edit', [ContentController::class, 'edit'])->name('edit');
            Route::patch('/{content_object}', [ContentController::class, 'update'])->name('update');
        });

        // ## Deleting
        Route::delete('/{content_object}', [ContentController::class, 'destroy'])
            ->middleware('can:delete,content_object')
            ->name('destroy');

        // ## Publishing
        Route::patch('/{content_object}/publish', [ContentController::class, 'publish'])
            ->middleware('can:publish_content')
            ->name('publish');

        // ## Archiving
        Route::patch('/{content_object}/archive', [ContentController::class, 'archive'])
            ->middleware('can:manage_archived')
            ->name('archive');

        // ## Content Types
        Route::middleware('can:manage_content_types')->prefix('types')->name('types.')->group(function() {
            Route::post('/', [ContentTypeController::class, 'store'])->name('store');
            Route::patch('/{type}', [ContentTypeController::class, 'update'])->name('update');
            Route::delete('/{type}', [ContentTypeController::class, 'destroy'])->name('destroy');
        });

        // ## Tags
        Route::prefix('tags')->name('tags.')->middleware('can:manage_tags')->group(function() {
            Route::post('/', [TagsController::class, 'store'])->name('store');
            Route::delete('/{tag}', [TagsController::class, 'destroy'])->name('destroy');
        });
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
