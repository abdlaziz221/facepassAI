<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Page d'accueil
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Dashboard central (redirige selon le rôle)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $user = Auth::user();
    return match($user->role ?? 'none') {
        'administrateur' => redirect()->route('admin.dashboard'),
        'gestionnaire'   => redirect()->route('gestionnaire.dashboard'),
        'consultant'     => redirect()->route('consultant.dashboard'),
        'employe'        => redirect()->route('employe.dashboard'),
        default          => view('dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Dashboard Administrateur
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Dashboard Gestionnaire
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('gestionnaire')->name('gestionnaire.')->group(function () {
    Route::get('/dashboard', fn() => view('gestionnaire.dashboard'))->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Dashboard Consultant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('consultant')->name('consultant.')->group(function () {
    Route::get('/dashboard', fn() => view('consultant.dashboard'))->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Dashboard Employé
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('employe')->name('employe.')->group(function () {
    Route::get('/dashboard', fn() => view('employe.dashboard'))->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Profil utilisateur
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Routes d'authentification (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';