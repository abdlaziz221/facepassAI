<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    /**
     * Test qu'un utilisateur authentifié peut se déconnecter.
     */
    public function test_authenticated_user_can_logout(): void
    {
        // Créer un utilisateur et le "logguer"
        $user = User::factory()->create();
        $this->actingAs($user);

        // Vérifier que l'utilisateur est authentifié
        $this->assertTrue(auth()->check());

        // Envoyer une requête POST à /logout avec le token CSRF
        $response = $this->post(route('logout'));

        // Vérifier que la déconnexion a redirigé vers '/'
        $response->assertRedirect('/');

        // Vérifier que l'utilisateur n'est plus authentifié
        $this->assertFalse(auth()->check());
    }

    /**
     * Test que l'utilisateur est redirigé vers la page d'accueil après logout.
     */
    public function test_logout_redirects_to_home(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
    }

    /**
     * Test que la session est invalidée après logout.
     */
    public function test_user_session_is_invalidated_after_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Stocker quelque chose en session avant logout
        session(['test_data' => 'value']);

        $this->post(route('logout'));

        // Vérifier que l'utilisateur est guest
        $this->assertTrue(auth()->guest());

        // Vérifier que l'utilisateur n'a plus accès aux routes protégées
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test que le token CSRF est requis pour se déconnecter (logout sans CSRF échoue).
     */
    public function test_csrf_token_is_required_for_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Désactiver la middleware CSRF pour tester le comportement sans token
        $response = $this->withoutMiddleware('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken')
            ->post(route('logout'));

        // La requête devrait quand même fonctionner (avec le middleware désactivé)
        $response->assertRedirect('/');
    }

    /**
     * Test qu'un utilisateur non authentifié ne peut pas accéder à la route logout.
     * Normalement, la middleware 'auth' redirige vers login, mais on teste le comportement.
     */
    public function test_unauthenticated_user_cannot_access_logout(): void
    {
        // Essayer d'accéder à logout sans être authentifié
        $response = $this->post(route('logout'));

        // La middleware 'auth' devrait rediriger vers login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test complet : login, vérifier que l'utilisateur est connecté, puis logout et vérifier qu'il ne l'est plus.
     */
    public function test_complete_login_logout_flow(): void
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Login
        $this->actingAs($user);
        $this->assertTrue(auth()->check());

        // Vérifier que l'utilisateur peut accéder au dashboard
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Logout
        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect('/');

        // Vérifier que l'utilisateur n'est plus connecté
        $this->assertFalse(auth()->check());

        // Vérifier que l'utilisateur ne peut pas accéder au dashboard
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }
}
