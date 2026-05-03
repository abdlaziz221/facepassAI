<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Page d'accueil
|--------------------------------------------------------------------------
| Redirige automatiquement :
|  - vers /dashboard si l'utilisateur est connecté
|  - vers /login sinon
*/
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Dashboard (utilisateurs connectés)
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
