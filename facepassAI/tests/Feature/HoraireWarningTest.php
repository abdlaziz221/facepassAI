<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\EmployeProfile;
use App\Models\JoursTravail;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests de l'avertissement avant modification des horaires
 * (Sprint 4 Horaires carte 4, US-043).
 *
 * Quand un admin modifie la configuration, si des pointages existent déjà :
 *   - Compter les pointages concernés et les afficher
 *   - Demander une confirmation explicite (case à cocher / modale)
 *   - Loguer la modification avec le nombre de pointages préexistants
 */
class HoraireWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $admin = Administrateur::factory()->create();
        $admin->assignRole(Role::Administrateur->value);
        $this->actingAs($admin);
    }

    /**
     * Helper : crée N pointages dans la base.
     */
    protected function createPointages(int $count): void
    {
        $profile = EmployeProfile::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            Pointage::factory()->for($profile, 'employe')->create();
        }
    }

    /** Helper : payload valide. */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'jours_ouvrables'   => ['lundi', 'mardi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '17:00',
        ], $overrides);
    }

    // ============================================================
    // Compter les pointages concernés
    // ============================================================

    public function test_la_vue_expose_le_nombre_de_pointages_existants(): void
    {
        $this->createPointages(3);

        $this->get('/admin/horaires')
            ->assertStatus(200)
            ->assertSee('3 pointage(s) existent déjà')
            ->assertSee('Attention');
    }

    public function test_la_vue_n_affiche_pas_d_avertissement_si_aucun_pointage(): void
    {
        $this->assertEquals(0, Pointage::count());

        $this->get('/admin/horaires')
            ->assertStatus(200)
            ->assertDontSee('Attention — 0 pointage');
    }

    // ============================================================
    // Modale de confirmation
    // ============================================================

    public function test_update_refuse_sans_confirmation_si_pointages_existent(): void
    {
        $this->createPointages(2);

        $response = $this->put('/admin/horaires', $this->validPayload());

        $response->assertSessionHasErrors('confirm');
        $errors = session('errors')->get('confirm');
        $this->assertStringContainsString('2 pointage', $errors[0]);
    }

    public function test_update_accepte_avec_confirmation_si_pointages_existent(): void
    {
        $this->createPointages(5);

        $this->put('/admin/horaires', $this->validPayload([
            'confirm' => '1',
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.horaires.edit'));
    }

    public function test_update_passe_sans_confirmation_si_aucun_pointage(): void
    {
        // Pas de pointage → pas besoin de confirm
        $this->assertEquals(0, Pointage::count());

        $this->put('/admin/horaires', $this->validPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.horaires.edit'));
    }

    public function test_confirm_doit_etre_truthy_pour_etre_accepte(): void
    {
        $this->createPointages(1);

        // confirm=0 ou string vide → refus
        $this->put('/admin/horaires', $this->validPayload([
            'confirm' => '0',
        ]))->assertSessionHasErrors('confirm');
    }

    // ============================================================
    // Log de la modification
    // ============================================================

    public function test_log_info_avec_le_nombre_de_pointages_preexistants(): void
    {
        $this->createPointages(7);
        Log::spy();

        $this->put('/admin/horaires', $this->validPayload([
            'confirm' => '1',
        ]));

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'horaires')
                    && isset($context['pointages_preexistants'])
                    && $context['pointages_preexistants'] === 7;
            })
            ->once();
    }

    public function test_log_contient_les_anciennes_et_nouvelles_valeurs(): void
    {
        $this->createPointages(1);
        Log::spy();

        // Set initial config
        JoursTravail::current()->update([
            'heure_arrivee' => '09:00',
        ]);

        $this->put('/admin/horaires', $this->validPayload([
            'heure_arrivee' => '07:30',
            'confirm'       => '1',
        ]));

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return isset($context['anciennes_valeurs'])
                    && isset($context['nouvelles_valeurs'])
                    && str_contains($context['nouvelles_valeurs']['heure_arrivee'] ?? '', '07:30');
            })
            ->once();
    }

    // ============================================================
    // Message de succès enrichi
    // ============================================================

    public function test_message_succes_mentionne_le_nombre_de_pointages_preexistants(): void
    {
        $this->createPointages(4);

        $response = $this->put('/admin/horaires', $this->validPayload([
            'confirm' => '1',
        ]));

        $this->assertStringContainsString('4 pointage', session('success'));
    }

    public function test_message_succes_simple_si_aucun_pointage(): void
    {
        $this->put('/admin/horaires', $this->validPayload());

        $success = session('success');
        $this->assertStringContainsString('enregistrée', $success);
        $this->assertStringNotContainsString('pointage', $success);
    }
}
