<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeController;
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
| Dashboard adaptatif selon le rôle (Sprint 1, US-016)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profil utilisateur (auto-suppression désactivée — Sprint 6)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
     Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markRead');
    Route::get('/notifications/summary', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('notifications.summary');
});

/*
|--------------------------------------------------------------------------
| CRUD Employés (Sprint 2, US-020/021/022)
|--------------------------------------------------------------------------
| L'autorisation est gérée par EmployeProfilePolicy (auto-discovery)
| via authorizeResource() dans le constructeur du controller.
| Le paramètre {profile} est lié au modèle EmployeProfile.
*/
Route::middleware('auth')
    ->resource('employes', EmployeController::class)
    ->parameters(['employes' => 'profile']);

/*
|--------------------------------------------------------------------------
| Routes protégées par rôle (Sprint 1, US-015) — exemples
|--------------------------------------------------------------------------
*/


   Route::middleware(['auth', 'role:administrateur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // ... routes existantes ...
        Route::get('/horaires',  [\App\Http\Controllers\HoraireConfigController::class, 'edit'])->name('horaires.edit');
        Route::put('/horaires',  [\App\Http\Controllers\HoraireConfigController::class, 'update'])->name('horaires.update');

        // 👇 AJOUTE CES 3 LIGNES POUR LES JOURS FÉRIÉS
        Route::get('/jours-feries',                    [\App\Http\Controllers\JourFerieController::class, 'index'])->name('jours-feries.index');
        Route::post('/jours-feries',                   [\App\Http\Controllers\JourFerieController::class, 'store'])->name('jours-feries.store');
        Route::delete('/jours-feries/{jour}',          [\App\Http\Controllers\JourFerieController::class, 'destroy'])->name('jours-feries.destroy');
    });

Route::middleware(['auth', 'role:gestionnaire|administrateur'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::get('/test', fn () => '<h1>✅ Espace Gestionnaire</h1><p>Accès autorisé. Routes Sprint 2-4 à venir.</p>')
            ->name('test');
    });

Route::middleware(['auth', 'role:consultant|gestionnaire|administrateur'])
    ->prefix('consultation')
    ->name('consultation.')
    ->group(function () {
        Route::get('/test', fn () => '<h1>✅ Espace Consultation</h1><p>Accès autorisé. Routes Sprint 5 à venir.</p>')
            ->name('test');
    });

/*
|--------------------------------------------------------------------------
| Routes d'authentification (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';


use App\Http\Controllers\PointageController;

// // Sprint 3 — Pointage biométrique public (kiosque)
// Route::post('/pointages', [PointageController::class, 'store'])->name('pointages.store');
// Sprint 3 — Pointage biométrique (kiosque public)
Route::get('/pointer', [PointageController::class, 'create'])->name('pointages.create');
// Sprint 4 US-036 — Pointage manuel (gestionnaire / admin)
Route::middleware(['auth', 'role:gestionnaire|administrateur'])->group(function () {
    Route::get('/pointages/manuel',  [PointageController::class, 'manualCreate'])->name('pointages.manual.create');
    Route::post('/pointages/manuel', [PointageController::class, 'manualStore'])->name('pointages.manual.store');
});
Route::post('/pointages', [PointageController::class, 'store'])->name('pointages.store');


// Sprint 4 Horaires US-050 — Demandes d'absence (employé)
Route::middleware(['auth', 'role:employe'])->group(function () {
    Route::get('/demandes-absence/create', [\App\Http\Controllers\DemandeAbsenceController::class, 'create'])->name('demandes-absence.create');
    Route::post('/demandes-absence',       [\App\Http\Controllers\DemandeAbsenceController::class, 'store'])->name('demandes-absence.store');
});

// Sprint 4 carte 10 (US-052) — Gestion des demandes par le gestionnaire/admin
// Route::middleware(['can:absences.view-all'])->group(function () {
Route::middleware(['auth', 'can:absences.view-all'])->group(function () {
    Route::get('/demandes-absence', [\App\Http\Controllers\DemandeAbsenceController::class, 'index'])
        ->name('demandes-absence.index');
    Route::get('/demandes-absence/{demande}', [\App\Http\Controllers\DemandeAbsenceController::class, 'show'])
        ->name('demandes-absence.show')
        ->whereNumber('demande');
});

// Route::middleware(['can:absences.validate'])->group(function () {
Route::middleware(['auth', 'can:absences.validate'])->group(function () {
    Route::post('/demandes-absence/{demande}/valider', [\App\Http\Controllers\DemandeAbsenceController::class, 'valider'])
        ->name('demandes-absence.valider')
        ->whereNumber('demande');
    Route::post('/demandes-absence/{demande}/refuser', [\App\Http\Controllers\DemandeAbsenceController::class, 'refuser'])
        ->name('demandes-absence.refuser')
        ->whereNumber('demande');
});
