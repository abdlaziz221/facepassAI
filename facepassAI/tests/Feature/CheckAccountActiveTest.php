<?php

namespace Tests\Feature;

use App\Models\Employe;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sprint 1 — US-014
 * Tests du middleware CheckAccountActive et du blocage à la connexion
 * pour les comptes désactivés (est_actif = false).
 */
class CheckAccountActiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /* ================================================================
       Connexion
       ================================================================ */

    public function test_active_user_can_login(): void
    {
        $user = Employe::factory()->create([
            'email'     => 'active@test.com',
            'password'  => Hash::make('password'),
            'est_actif' => true,
        ]);
        $user->assignRole('employe');

        $response = $this->post('/login', [
            'email'    => 'active@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = Employe::factory()->create([
            'email'     => 'inactive@test.com',
            'password'  => Hash::make('password'),
            'est_actif' => false,
        ]);
        $user->assignRole('employe');

        $response = $this->post('/login', [
            'email'    => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_login_shows_clear_french_message(): void
    {
        $user = Employe::factory()->create([
            'email'     => 'inactive2@test.com',
            'password'  => Hash::make('password'),
            'est_actif' => false,
        ]);
        $user->assignRole('employe');

        $response = $this->post('/login', [
            'email'    => 'inactive2@test.com',
            'password' => 'password',
        ]);

        $errors = session('errors')->getMessages()['email'][0] ?? '';
        $this->assertStringContainsString('compte', strtolower($errors));
        $this->assertStringContainsString('administrateur', strtolower($errors));
    }

    /* ================================================================
       Désactivation en cours de session
       ================================================================ */

    public function test_user_logged_in_is_logged_out_when_deactivated(): void
    {
        $user = Employe::factory()->create([
            'email'     => 'foo@test.com',
            'password'  => Hash::make('password'),
            'est_actif' => true,
        ]);
        $user->assignRole('employe');

        // Connexion OK
        $this->actingAs($user);
        $this->get('/dashboard')->assertOk();

        // Désactivation par admin (ailleurs)
        $user->update(['est_actif' => false]);

        // À la prochaine requête, l'utilisateur est dégagé
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
