<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 1 — US-014 : déconnecte automatiquement les comptes
 * désactivés en cours de session.
 *
 * Si un utilisateur est connecté mais que son champ `est_actif`
 * passe à false (action admin), il est immédiatement déloggé
 * à sa prochaine requête web et redirigé vers /login avec un
 * message clair "Contacter l'administrateur".
 */
class CheckAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->est_actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.',
                ]);
        }

        return $next($request);
    }
}
