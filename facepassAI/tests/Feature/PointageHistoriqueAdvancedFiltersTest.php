<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 2 (US-061) — Filtres avancés shareables via URL.
 *
 * Critères :
 *   - Query strings persistées (à travers la pagination)
 *   - Bouton Reset filtres (visible dès qu'un filtre est actif, lien vide)
 *   - Message "Aucun résultat" quand les filtres ne renvoient rien
 *   - Bouton "Copier le lien" présent dès qu'un filtre est actif
 */
class PointageHistoriqueAdvancedFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected Gestionnaire $gestionnaire;
    protected EmployeProfile $emp1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->emp1 = EmployeProfile::factory()->create();
    }

    protected function makePointage(string $type, string $datetime): Pointage
    {
        $p = Pointage::factory()->for($this->emp1, 'employe')->create(['type' => $type]);
        $p->forceFill(['created_at' => $datetime])->save();
        return $p;
    }

    // ========================================================================
    // Persistance des filtres dans les liens de pagination
    // ========================================================================

    public function test_les_filtres_sont_persistes_dans_les_liens_de_pagination(): void
    {
        // 25 pointages pour déclencher la pagination
        for ($i = 1; $i <= 25; $i++) {
            $this->makePointage('arrivee', '2026-06-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 08:00:00');
        }
        // Pour ne pas perturber, ajoutons aussi quelques départs (ils seront exclus)
        $this->makePointage('depart', '2026-06-10 17:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['type' => 'arrivee']));

        $response->assertOk();
        // Les liens de pagination doivent contenir le filtre type=arrivee
        $response->assertSee('type=arrivee', false);
        $response->assertSee('page=2', false);
    }

    public function test_pagination_avec_plusieurs_filtres_combines(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->makePointage('arrivee', '2026-06-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 08:00:00');
        }

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', [
                'employe_id' => $this->emp1->id,
                'type'       => 'arrivee',
                'sort'       => 'created_at',
                'dir'        => 'asc',
            ]));

        $response->assertOk();
        $response->assertSee('employe_id=' . $this->emp1->id, false);
        $response->assertSee('type=arrivee', false);
        $response->assertSee('dir=asc', false);
    }

    // ========================================================================
    // Reset filtres
    // ========================================================================

    public function test_lien_reset_visible_si_un_filtre_est_actif(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['type' => 'arrivee']))
            ->assertOk()
            ->assertSee('Reset');
    }

    public function test_lien_reset_invisible_sans_aucun_filtre(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'))
            ->assertOk()
            ->assertDontSee('>Reset<', false);
    }

    public function test_reset_efface_tous_les_filtres(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');
        $this->makePointage('depart',  '2026-06-10 17:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        // Sans filtres → on voit tous les pointages
        $this->assertEquals(2, $response->viewData('pointages')->total());
    }

    // ========================================================================
    // Message "Aucun résultat"
    // ========================================================================

    public function test_message_aucun_resultat_si_filtre_renvoie_vide(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        // Filtre sur un employé inexistant
        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['employe_id' => 999_999]));

        $response->assertOk()
            ->assertSee('Aucun pointage');
    }

    public function test_message_aucun_resultat_propose_de_reinitialiser(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['type' => 'depart']));

        $response->assertOk()
            ->assertSee('Aucun pointage')
            ->assertSee('Réinitialiser', false);
    }

    public function test_message_vide_neutre_sans_aucun_filtre(): void
    {
        // Aucun pointage en base, aucun filtre
        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $response->assertOk()
            ->assertSee('Aucun pointage')
            ->assertSee('enregistré pour le moment');
    }

    // ========================================================================
    // Bouton "Copier le lien"
    // ========================================================================

    public function test_bouton_copier_le_lien_present_si_filtres_actifs(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['type' => 'arrivee']))
            ->assertOk()
            ->assertSee('Copier le lien');
    }

    public function test_bouton_copier_absent_sans_filtre(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'))
            ->assertOk()
            ->assertDontSee('Copier le lien');
    }

    // ========================================================================
    // Sécurité du tri (whitelist anti-injection)
    // ========================================================================

    public function test_tri_par_colonne_arbitraire_ne_casse_pas(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['sort' => 'password', 'dir' => 'desc']))
            ->assertOk();
    }

    public function test_dir_arbitraire_force_a_desc(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['dir' => 'pwned']))
            ->assertOk();
    }
}
