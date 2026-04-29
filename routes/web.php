<?php

use App\Enums\PostType;
use App\Http\Controllers\Orgs\CategoryController;
use App\Http\Controllers\Orgs\PostController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::inertia('dashboard', 'dashboard')->name('dashboard');
        Route::get('{current_org}/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('{current_org}/categories/{type}', [CategoryController::class, 'index'])
            ->whereIn('type', PostType::values())
            ->name('categories.byType');
        Route::get('{current_org}/posts', [PostController::class, 'index'])->name('posts.index');
        Route::post('{current_org}/posts', [PostController::class, 'store'])->name('posts.store');
        Route::patch('{current_org}/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('{current_org}/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::get('{current_org}/posts/{type}', [PostController::class, 'index'])
            ->whereIn('type', PostType::values())
            ->name('posts.byType');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

Route::get('/about', function () {
    return inertia('about', [
        'message' => 'Тут будет информация о проекте!',
    ]);
});

require __DIR__.'/settings.php';
