<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mp;
use Illuminate\Http\Request;
use App\Http\Controllers\MP\MpController;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\LanguageController;

Route::post('/set-language', [LanguageController::class, 'switch'])->name('language.switch');





Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');
/*
|--------------------------------------------------------------------------
| Web Routes - Civic Platform & MP Dashboard Views
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. CITIZEN / PUBLIC ROUTES
// ==========================================

// Public landing page for Citizens
Route::get('/', function () {
    $mp = Mp::latest()->first(); 
    return view('citizen.index', compact('mp'));
})->name('citizen.home');


// ==========================================
// 2. MP PORTAL VIEWS (JWT + Alpine.js Handled)
// ==========================================

Route::get('mp/login', function () {
    return view('mp.auth.login');
})->name('login');

Route::prefix('mp')->group(function () {

    Route::get('/hotspots', function () {
        return view('mp.hotspots');
    })->name('mp.hotspots');

    Route::get('/priorities', [MpController::class, 'prioritiesView'])->name('mp.priorities');

    Route::get('/requests', [MpController::class, 'requestsView'])->name('mp.requests');

    Route::get('/matrix', [MpController::class, 'matrixView'])->name('mp.matrix');
    // MP Profile View Shell
    Route::get('/profile/show', function () {
        return view('mp.profile.show');
    })->name('mp.profile.show');

    Route::get('/profile/edit', function () {
        return view('mp.profile.edit');
    })->name('mp.profile.edit');

    Route::get('/profile/password', function () {
        return view('mp.profile.password');
    })->name('mp.profile.password');
    // MP Dashboard View Shell
    Route::get('/dashboard', function () {
        return view('mp.dashboard');
    })->name('mp.dashboard');

    // MP Analytics View Shell
    Route::get('/analytics', function () {
        return view('mp.analytics');
    })->name('analytics');

});


// ==========================================
// 3. FALLBACK ROUTE
// ==========================================

Route::fallback(function () {
    return redirect()->route('citizen.home');
});