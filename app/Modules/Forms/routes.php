<?php

use App\Modules\Forms\LeadController;
use App\Modules\Forms\SubmitController;
use Illuminate\Support\Facades\Route;

// Public submit endpoint: no session/CSRF on purpose (public pages are cached and cookie-free).
// Protection = honeypot field + rate limit + server-side validation.
Route::post('/{client}/submit/{section}', [SubmitController::class, 'store'])
    ->where('client', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->whereNumber('section')
    ->middleware('throttle:forms-submit')
    ->name('site.submit');

// Leads inbox in the client dashboard.
Route::middleware(['web', 'auth', 'manage'])->prefix('manage/{client}')->name('manage.')->group(function () {
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('leads/read-all', [LeadController::class, 'markAllRead'])->name('leads.read-all');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
    Route::post('leads/{lead}/read', [LeadController::class, 'mark'])->whereNumber('lead')->name('leads.read');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->whereNumber('lead')->middleware('can:leads.delete,client')->name('leads.destroy');
});
