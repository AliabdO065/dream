<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardLocaleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Manage;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

// The dashboard's own language (English/Arabic) — separate from the languages a client's public site offers.
// No 'auth' needed: a visitor can switch language on the login page itself.
Route::post('/dashboard-locale/{code}', [DashboardLocaleController::class, 'update'])->name('dashboard-locale.update');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/home', HomeController::class)->name('home');
});

// ---- Platform admin area: admins AND assistants. Routes marked `super` are for admins only. ----
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('clients', [Admin\ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [Admin\ClientController::class, 'create'])->name('clients.create');
    Route::get('clients/slug', [Admin\ClientController::class, 'slug'])->name('clients.slug');
    Route::post('clients', [Admin\ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}/edit', [Admin\ClientController::class, 'edit'])->name('clients.edit');
    Route::put('clients/{client}', [Admin\ClientController::class, 'update'])->name('clients.update');
    Route::delete('clients/{client}', [Admin\ClientController::class, 'destroy'])->middleware('super')->name('clients.destroy');
    Route::post('clients/{client}/members', [Admin\ClientController::class, 'addMember'])->name('clients.members.add');
    Route::delete('clients/{client}/members/{user}', [Admin\ClientController::class, 'removeMember'])->name('clients.members.remove');

    // the platform's own staff (who is an admin / assistant) — admins only
    Route::middleware('super')->group(function () {
        Route::get('admins', [Admin\AdminController::class, 'index'])->name('admins.index');
        Route::post('admins', [Admin\AdminController::class, 'store'])->name('admins.store');
        Route::put('admins/{user}/role', [Admin\AdminController::class, 'role'])->name('admins.role');
        Route::post('admins/{user}/password', [Admin\AdminController::class, 'resetPassword'])->middleware('throttle:30,1')->name('admins.password.reset');    });
});

// ---- Client dashboard (members of the client, and super admins) ----
// Any member may use a route here unless it carries a `can:` ability — see App\Support\Access for who has which.
Route::middleware(['auth', 'manage'])->prefix('manage/{client}')->name('manage.')->group(function () {
    Route::get('/', [Manage\DashboardController::class, 'index'])->name('dashboard');

    Route::get('sections', [Manage\SectionController::class, 'index'])->name('sections.index');
    Route::get('sections/create', [Manage\SectionController::class, 'create'])->name('sections.create');
    Route::post('sections', [Manage\SectionController::class, 'store'])->name('sections.store');
    Route::post('sections/reorder', [Manage\SectionController::class, 'reorder'])->name('sections.reorder');
    Route::get('sections/{section}/edit', [Manage\SectionController::class, 'edit'])->whereNumber('section')->name('sections.edit');
    Route::put('sections/{section}', [Manage\SectionController::class, 'update'])->whereNumber('section')->name('sections.update');
    Route::delete('sections/{section}', [Manage\SectionController::class, 'destroy'])->whereNumber('section')->middleware('can:sections.delete,client')->name('sections.destroy');
    Route::post('sections/{section}/toggle', [Manage\SectionController::class, 'toggle'])->whereNumber('section')->name('sections.toggle');
    Route::post('sections/{section}/move/{direction}', [Manage\SectionController::class, 'move'])->whereNumber('section')->whereIn('direction', ['up', 'down'])->name('sections.move');

    Route::middleware('can:profile.edit,client')->group(function () {
        Route::get('profile', [Manage\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [Manage\ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware('can:team.manage,client')->group(function () {
        Route::get('team', [Manage\TeamController::class, 'index'])->name('team.index');
        Route::post('team', [Manage\TeamController::class, 'store'])->name('team.store');
        Route::delete('team/{user}', [Manage\TeamController::class, 'destroy'])->whereNumber('user')->name('team.destroy');
    });
});
