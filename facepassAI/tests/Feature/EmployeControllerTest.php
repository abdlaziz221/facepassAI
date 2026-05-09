<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmployeProfile;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmployeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // S'assurer que les permissions sont seedées
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_admin_can_view_employes_list(): void
    {
        $admin = User::factory()->create(['est_actif' => true, 'role' => Role::Administrateur->value]);
        $admin->assignRole(Role::Administrateur->value);

        $response = $this->actingAs($admin)->get(route('employes.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employes.index');
    }

    public function test_employe_cannot_view_employes_list(): void
    {
        $employe = User::factory()->create(['est_actif' => true, 'role' => Role::Employe->value]);
        $employe->assignRole(Role::Employe->value);

        $response = $this->actingAs($employe)->get(route('employes.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_employe(): void
    {
        $admin = User::factory()->create(['est_actif' => true, 'role' => Role::Administrateur->value]);
        $admin->assignRole(Role::Administrateur->value);

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'matricule' => 'EMP-001',
            'poste' => 'Développeur',
            'departement' => 'IT',
            'salaire_brut' => 500000,
        ];

        $response = $this->actingAs($admin)->post(route('employes.store'), $data);

        $response->assertRedirect(route('employes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => Role::Employe->value,
        ]);

        $this->assertDatabaseHas('employes', [
            'matricule' => 'EMP-001',
            'departement' => 'IT',
        ]);
    }

    public function test_admin_can_delete_employe(): void
    {
        $admin = User::factory()->create(['est_actif' => true, 'role' => Role::Administrateur->value]);
        $admin->assignRole(Role::Administrateur->value);

        $profile = EmployeProfile::factory()->create();

        $response = $this->actingAs($admin)->delete(route('employes.destroy', $profile));

        $response->assertRedirect(route('employes.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('employes', [
            'id' => $profile->id,
        ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $profile->user_id,
            'est_actif' => 0,
        ]);
    }
}
