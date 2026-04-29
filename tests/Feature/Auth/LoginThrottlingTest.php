<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le throttling bloque après 3 tentatives.
     */
    public function test_login_throttling_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $throttleKey = 'test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // 1ère tentative échouée
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('2 tentative(s) avant blocage', session('errors')->get('email')[0]);

        // 2ème tentative échouée
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertStringContainsString('1 tentative(s) avant blocage', session('errors')->get('email')[0]);

        // 3ème tentative échouée -> Compteur à 0
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertStringContainsString('0 tentative(s) avant blocage', session('errors')->get('email')[0]);

        // 4ème tentative -> Blocage effectif avec temps restant
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertStringContainsString('Veuillez réessayer dans', session('errors')->get('email')[0]);
        $this->assertStringContainsString('secondes', session('errors')->get('email')[0]);
    }

    /**
     * Test que le compteur est réinitialisé après un succès.
     */
    public function test_login_reset_throttling_after_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $throttleKey = 'test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // Un échec pour diminuer le compteur
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        
        $this->assertEquals(2, RateLimiter::remaining($throttleKey, 3));

        // Une tentative réussie
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        
        // Vérifier que le compteur RateLimiter a été vidé (donc 3 tentatives restantes)
        $this->assertEquals(3, RateLimiter::remaining($throttleKey, 3));
    }
}
