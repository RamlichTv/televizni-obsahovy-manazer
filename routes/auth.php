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
use App\Http\Controllers\Tom\Module\ModuleCategoriesController;
use App\Http\Controllers\Tom\ModulesController;
use App\Http\Controllers\Tom\ProfilesController;
use App\Http\Controllers\Tom\Screen\BuildingsController;
use App\Http\Controllers\Tom\Screen\RoomsController;
use App\Http\Controllers\Tom\ScreensController;
use App\Http\Controllers\Tom\UsersController;
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

    /* ## SCREEN ROUTES ## */
    Route::middleware('can:manage_screens')->group(function () {
        Route::get('/screens', [ScreensController::class, 'index'])->name('screens.index');
        
        // ## Screens
        Route::prefix('screen')->name('screen.')->group(function () {
            Route::get('/create', [ScreensController::class, 'create'])->name('create');
            Route::post('/', [ScreensController::class, 'store'])->name('store');

            Route::get('/{screen}/edit', [ScreensController::class, 'edit'])->name('edit');
            Route::patch('/{screen}', [ScreensController::class, 'update'])->name('update');

            Route::delete('/{screen}', [ScreensController::class, 'destroy'])->name('destroy');
        });

        // ## Rooms
        Route::get('/rooms', [RoomsController::class, 'index'])->name('rooms.index');
        Route::prefix('room')->name('room.')->group(function () {
            Route::post('/', [RoomsController::class, 'store'])->name('store');
            Route::patch('/{room}', [RoomsController::class, 'update'])->name('update');
            Route::delete('/{room}', [RoomsController::class, 'destroy'])->name('destroy');
        });
        
        // ## Buildings
        Route::get('/buildings', [BuildingsController::class, 'index'])->name('buildings.index');
        Route::prefix('building')->name('building.')->group(function () {
            Route::post('/', [BuildingsController::class, 'store'])->name('store');
            Route::patch('/{building}', [BuildingsController::class, 'update'])->name('update');
            Route::delete('/{building}', [BuildingsController::class, 'destroy'])->name('destroy');
        });
    });

    /* ## PROFILE ROUTES ## */    
    Route::get('/profiles', [ProfilesController::class, 'index'])
        ->middleware('perms:create_profiles,edit_profiles,delete_profiles')
        ->name('profiles.index');
    Route::prefix('profile')->name('profile.')->group(function () {
        // ## Viewing
        Route::get('/{profile}', [ProfilesController::class, 'show'])
            ->middleware('perms:create_profiles,edit_profiles,delete_profiles')
            ->name('show');

        // ## Creating
        Route::get('/create', [ProfilesController::class, 'create'])
            ->middleware('can:create_profiles')
            ->name('create');
        Route::post('/', [ProfilesController::class, 'store'])
            ->middleware('can:create_profiles')
            ->name('store');
        
        // ## Editing
        Route::get('/{profile}/edit', [ProfilesController::class, 'edit'])
            ->middleware('can:update,profile')
            ->name('edit');
        Route::patch('/{profile}', [ProfilesController::class, 'update'])
            ->middleware('can:update,profile')
            ->name('update');
        
        // ## Deleting
        Route::delete('/{profile}', [ProfilesController::class, 'destroy'])
            ->middleware('can:delete,profile')
            ->name('destroy');
    });

    /* ## MODULE ROUTES ## */
    Route::get('/modules', [ModulesController::class, 'index'])->name('modules.index');
    Route::prefix('module')->name('module.')->group(function () {
        Route::get('/create', [ModulesController::class, 'create'])
            ->middleware('can:create_modules')
            ->name('create');

        Route::get('/{module}/edit', [ModulesController::class, 'edit'])
            ->middleware('can:update,module')
            ->name('edit');

        Route::post('/', [ModulesController::class, 'store'])
            ->middleware('can:create_modules')
            ->name('store');

        Route::patch('/{module}', [ModulesController::class, 'update'])
            ->middleware('can:update,module')
            ->name('update');

        Route::delete('/{module}', [ModulesController::class, 'destroy'])
            ->middleware('can:delete,module')
            ->name('destroy');

        Route::resource('categories', ModuleCategoriesController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('can:manage_module_categories');
    });

    /* ## USER MANAGEMENT ROUTES */
    Route::middleware('perms:manage_users,manage_acl')->group(function() {
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/user/{user}/edit', [UsersController::class, 'edit'])->name('edit');
        Route::patch('/user/{user}', [UsersController::class, 'update'])->name('update');
    });
    Route::middleware('can:manage_users')->prefix('user')->name('user.')->group(function() {
        Route::post('/register', [RegisteredUserController::class, 'store']);
        Route::get('/create', [UsersController::class, 'create'])->name('create');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
