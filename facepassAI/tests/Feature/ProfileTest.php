<?php

namespace Tests\Feature;

use App\Models\Employe;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du profil utilisateur Breeze, adaptés à notre app :
 * - On utilise Employe::factory() (pas User vanilla) pour avoir un rôle valide
 * - On a supprimé les tests d'auto-suppression de compte (feature désactivée Sprint 1 T11)
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = Employe::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = Employe::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name'  => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = Employe::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name'  => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
