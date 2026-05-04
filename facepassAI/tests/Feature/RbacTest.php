<?php

namespace Tests\Feature;

use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 1 — US-015
 * Tests couvrant les 4 rôles et leur accès aux routes protégées.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seeder les rôles & permissions spatie avant chaque test
        $this->seed(RolePermissionSeeder::class);
    }

    /* ================================================================
       Routes /admin/* — réservé Administrateur
       ================================================================ */

    public function test_employe_cannot_access_admin_routes(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole('employe');

        $this->actingAs($user)
             ->get('/admin/test')
             ->assertForbidden();
    }

    public function test_consultant_cannot_access_admin_routes(): void
    {
        $user = Consultant::factory()->create();
        $user->assignRole('consultant');

        $this->actingAs($user)
             ->get('/admin/test')
             ->assertForbidden();
    }

    public function test_gestionnaire_cannot_access_admin_routes(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole('gestionnaire');

        $this->actingAs($user)
             ->get('/admin/test')
             ->assertForbidden();
    }

    public function test_administrateur_can_access_admin_routes(): void
    {
        $user = Administrateur::factory()->create();
        $user->assignRole('administrateur');

        $this->actingAs($user)
             ->get('/admin/test')
             ->assertOk();
    }

    /* ================================================================
       Routes /gestion/* — Gestionnaire + Administrateur
       ================================================================ */

    public function test_employe_cannot_access_gestion_routes(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole('employe');

        $this->actingAs($user)
             ->get('/gestion/test')
             ->assertForbidden();
    }

    public function test_gestionnaire_can_access_gestion_routes(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole('gestionnaire');

        $this->actingAs($user)
             ->get('/gestion/test')
             ->assertOk();
    }

    public function test_administrateur_can_access_gestion_routes(): void
    {
        $user = Administrateur::factory()->create();
        $user->assignRole('administrateur');

        $this->actingAs($user)
             ->get('/gestion/test')
             ->assertOk();
    }

    /* ================================================================
       Routes /consultation/* — Consultant + Gestionnaire + Admin
       ================================================================ */

    public function test_employe_cannot_access_consultation_routes(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole('employe');

        $this->actingAs($user)
             ->get('/consultation/test')
             ->assertForbidden();
    }

    public function test_consultant_can_access_consultation_routes(): void
    {
        $user = Consultant::factory()->create();
        $user->assignRole('consultant');

        $this->actingAs($user)
             ->get('/consultation/test')
             ->assertOk();
    }

    /* ================================================================
       Permission directe (employes.create)
       ================================================================ */

    public function test_employe_cannot_create_employes(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole('employe');

        $this->actingAs($user)
             ->get('/employes/create')
             ->assertForbidden();
    }

    public function test_gestionnaire_can_create_employes(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole('gestionnaire');

        $this->actingAs($user)
             ->get('/employes/create')
             ->assertOk();
    }

    /* ================================================================
       Accès anonyme refusé
       ================================================================ */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/test')->assertRedirect('/login');
        $this->get('/gestion/test')->assertRedirect('/login');
        $this->get('/consultation/test')->assertRedirect('/login');
    }
}
