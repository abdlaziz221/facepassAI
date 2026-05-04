<?php

use App\Http\Middleware\CheckAccountActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Sprint 1, US-014 : déconnexion auto des comptes désactivés
        |--------------------------------------------------------------------------
        | Le middleware tourne sur toutes les requêtes web. Si l'utilisateur
        | connecté a est_actif = false, il est immédiatement déloggé.
        */
        $middleware->web(append: [
            CheckAccountActive::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Sprint 1, US-015 : alias des middlewares RBAC spatie
        |--------------------------------------------------------------------------
        | Permet d'écrire dans les routes :
        |   ->middleware('role:administrateur')
        |   ->middleware('permission:employes.create')
        |   ->middleware('role_or_permission:gestionnaire|absences.validate')
        */
        $middleware->alias([
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
