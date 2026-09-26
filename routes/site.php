<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// "/" is the platform's own marketing site (the client named in config platform.home_client).
// It alone gets the session ("web"), so its header can show the signed-in user's dashboard link.
Route::get('/', [SiteController::class, 'home'])->middleware('web')->name('platform.home');

// Public client site at /{slug}. Catch-all, so it must stay the last route registered.
// Slugs are validated against config('platform.reserved_slugs') when clients are created.
Route::get('/{client}', [SiteController::class, 'show'])
    ->where('client', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.show');
