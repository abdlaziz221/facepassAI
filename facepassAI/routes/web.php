<?php

use App\Http\Controllers\DashboardController;
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
| Dashboard adaptatif selon le rôle (Sprint 1, US-016)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profil utilisateur
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Sprint 1 : auto-suppression désactivée pour des raisons de sécurité.
    // Seul l'admin pourra supprimer un compte (Sprint 6, US-090).
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Routes protégées par rôle (Sprint 1, US-015)
|--------------------------------------------------------------------------
| Sous-groupes utilisant les aliases 'role' et 'permission' enregistrés
| dans bootstrap/app.php. Les controllers métier seront ajoutés
| progressivement aux Sprints 2-6.
*/

// === ADMINISTRATEUR uniquement ===
Route::middleware(['auth', 'role:administrateur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Tests / placeholders Sprint 6
        Route::get('/test', fn () => '<h1>✅ Espace Administrateur</h1><p>Accès autorisé. Routes Sprint 6 à venir : gestionnaires, logs.</p>')
            ->name('test');
    });

// === GESTIONNAIRE et plus haut ===
Route::middleware(['auth', 'role:gestionnaire|administrateur'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::get('/test', fn () => '<h1>✅ Espace Gestionnaire</h1><p>Accès autorisé. Routes Sprint 2-4 à venir : employés, horaires, validation absences.</p>')
            ->name('test');
    });

// === CONSULTANT et plus haut ===
Route::middleware(['auth', 'role:consultant|gestionnaire|administrateur'])
    ->prefix('consultation')
    ->name('consultation.')
    ->group(function () {
        Route::get('/test', fn () => '<h1>✅ Espace Consultation</h1><p>Accès autorisé. Routes Sprint 5 à venir : rapports, exports.</p>')
            ->name('test');
    });

// === Exemple : route protégée par PERMISSION (et pas seulement rôle) ===
Route::get('/employes/create', fn () => '<h1>Formulaire ajout employé</h1><p>Sprint 2 — TODO</p>')
    ->middleware(['auth', 'permission:employes.create'])
    ->name('employes.create');

/*
|--------------------------------------------------------------------------
| Routes d'authentification (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
