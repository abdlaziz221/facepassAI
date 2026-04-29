<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Clé de limitation (email + IP)
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // 1 & 2. Limiter à 3 tentatives et blocage 60s
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            
            // 3. Message clair indiquant le temps restant avant déblocage
            throw ValidationException::withMessages([
                'email' => "Trop de tentatives de connexion. Veuillez réessayer dans $seconds secondes.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            // Incrémente le compteur avec un blocage de 60 secondes (par défaut pour Laravel throttling)
            RateLimiter::hit($throttleKey, 60);
            
            // 4. Afficher le nombre de tentatives restantes avant blocage
            $attemptsLeft = RateLimiter::remaining($throttleKey, 3);
            
            throw ValidationException::withMessages([
                'email' => "Identifiants incorrects. Il vous reste $attemptsLeft tentative(s) avant blocage.",
            ]);
        }

        // 5. Réinitialiser le compteur après une connexion réussie
        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
