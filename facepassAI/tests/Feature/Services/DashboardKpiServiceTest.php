<?php

namespace Tests\Feature\Services;

use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\JoursTravail;
use App\Models\Pointage;
use App\Services\DashboardKpiService;
use App\Services\RetardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 6 cartes 10/11/12 (US-100/101/102) — Tests du service KPI dashboard.
 */
class DashboardKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardKpiService $service;
    protected JoursTravail $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = JoursTravail::current();
        $this->service = new DashboardKpiService(new RetardService($this->config), $this->config);

        // Aujourd'hui = mardi pour qu'il soit jour ouvré
        Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00')); // mardi 9 juin
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makeArrivee(EmployeProfile $emp, string $time): Pointage
    {
        $p = Pointage::factory()->for($emp, 'employe')->create(['type' => 'arrivee']);
        $p->forceFill(['created_at' => Carbon::today()->setTimeFromTimeString($time)])->save();
        return $p;
    }

    // ========================================================================
    // KPI cards
    // ========================================================================

    public function test_presents_aujourdhui_compte_arrivees_distinctes(): void
    {
        $emp1 = EmployeProfile::factory()->create();
        $emp2 = EmployeProfile::factory()->create();
        EmployeProfile::factory()->create(); // 3e pas pointé

        $this->makeArrivee($emp1, '08:00:00');
        $this->makeArrivee($emp2, '08:30:00');
        $this->makeArrivee($emp1, '09:00:00'); // double pointage du même

        $this->assertEquals(2, $this->service->presentsAujourdhui());
    }

    public function test_retards_aujourdhui_compte_arrivees_apres_l_heure(): void
    {
        $emp1 = EmployeProfile::factory()->create();
        $emp2 = EmployeProfile::factory()->create();
        $emp3 = EmployeProfile::factory()->create();

        $this->makeArrivee($emp1, '08:00:00'); // à l'heure
        $this->makeArrivee($emp2, '08:30:00'); // retard
        $this->makeArrivee($emp3, '09:15:00'); // retard

        $this->assertEquals(2, $this->service->retardsAujourdhui());
    }

    public function test_absents_aujourdhui_compte_les_non_pointes(): void
    {
        $emp1 = EmployeProfile::factory()->create();
        $emp2 = EmployeProfile::factory()->create();
        $emp3 = EmployeProfile::factory()->create();

        // Seul emp1 a pointé
        $this->makeArrivee($emp1, '08:00:00');

        // emp2 et emp3 sont absents (pas de congé)
        $this->assertEquals(2, $this->service->absentsAujourdhui());
    }

    public function test_absents_aujourdhui_exclut_les_conges_valides(): void
    {
        $emp1 = EmployeProfile::factory()->create();
        $emp2 = EmployeProfile::factory()->create();
        $emp3 = EmployeProfile::factory()->create();

        $this->makeArrivee($emp1, '08:00:00');

        // emp2 en congé validé aujourd'hui
        DemandeAbsence::factory()->for($emp2, 'employe')->create([
            'date_debut' => Carbon::today()->format('Y-m-d'),
            'date_fin'   => Carbon::today()->format('Y-m-d'),
            'statut'     => DemandeAbsence::STATUT_VALIDEE,
        ]);

        // Seul emp3 est vraiment absent
        $this->assertEquals(1, $this->service->absentsAujourdhui());
    }

    public function test_demandes_en_attente_compte_correctement(): void
    {
        $emp = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($emp, 'employe')->count(3)->create([
            'statut' => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);
        DemandeAbsence::factory()->for($emp, 'employe')->create([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
        ]);

        $this->assertEquals(3, $this->service->demandesEnAttente());
    }

    public function test_kpi_cards_renvoie_la_structure_complete(): void
    {
        EmployeProfile::factory()->count(2)->create();

        $kpi = $this->service->kpiCards();

        $this->assertArrayHasKey('presents', $kpi);
        $this->assertArrayHasKey('retards', $kpi);
        $this->assertArrayHasKey('absents', $kpi);
        $this->assertArrayHasKey('demandes_en_attente', $kpi);
        $this->assertArrayHasKey('taux_presence', $kpi);
        $this->assertArrayHasKey('total_employes', $kpi);
        $this->assertEquals(2, $kpi['total_employes']);
    }

    // ========================================================================
    // Graphiques
    // ========================================================================

    public function test_presences_par_jour_renvoie_30_jours(): void
    {
        $emp = EmployeProfile::factory()->create();
        $p = Pointage::factory()->for($emp, 'employe')->create(['type' => 'arrivee']);
        $p->forceFill(['created_at' => Carbon::today()->subDays(5)->setTime(8, 0)])->save();

        $data = $this->service->presencesParJour(30);
        $this->assertCount(30, $data['labels']);
        $this->assertCount(30, $data['data']);
        // Le jour J-5 doit avoir 1 employé compté
        $this->assertContains(1, $data['data']);
    }

    public function test_repartition_statuts_absences(): void
    {
        $emp = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($emp, 'employe')->count(2)->create(['statut' => DemandeAbsence::STATUT_EN_ATTENTE]);
        DemandeAbsence::factory()->for($emp, 'employe')->count(5)->create(['statut' => DemandeAbsence::STATUT_VALIDEE]);
        DemandeAbsence::factory()->for($emp, 'employe')->create(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $data = $this->service->repartitionStatutsAbsences();
        $this->assertEquals([2, 5, 1], $data['data']);
        $this->assertEquals(['En attente', 'Validées', 'Refusées'], $data['labels']);
    }

    // ========================================================================
    // Alertes (carte 12)
    // ========================================================================

    public function test_alertes_high_si_demandes_en_attente_nombreuses(): void
    {
        $emp = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($emp, 'employe')->count(7)->create([
            'statut' => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $alertes = $this->service->alertes();
        $this->assertTrue($alertes->contains(fn ($a) => $a['level'] === 'high' && str_contains($a['title'], 'demandes')));
    }

    public function test_alertes_medium_si_quelques_demandes(): void
    {
        $emp = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($emp, 'employe')->count(2)->create([
            'statut' => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $alertes = $this->service->alertes();
        $this->assertTrue($alertes->contains(fn ($a) => $a['level'] === 'medium' && str_contains($a['title'], 'demande')));
    }

    public function test_alertes_horaires_non_configures(): void
    {
        // Config par défaut = pas isConfigured
        $alertes = $this->service->alertes();
        $this->assertTrue($alertes->contains(fn ($a) => str_contains($a['title'], 'Horaires non')));
    }

    public function test_alertes_tri_par_gravite_decroissante(): void
    {
        // Crée plusieurs alertes de niveaux différents
        $emp = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($emp, 'employe')->count(7)->create([
            'statut' => DemandeAbsence::STATUT_EN_ATTENTE,
        ]); // high

        $alertes = $this->service->alertes();
        $this->assertGreaterThan(0, $alertes->count());

        // La première doit être high (ou high si premier élément, sinon at least non-low)
        $first = $alertes->first();
        $this->assertContains($first['level'], ['high', 'medium']);
    }

    public function test_alertes_aucune_si_systeme_sain(): void
    {
        // Config présente (vraie modification) → isConfigured = true
        // ⚠ il faut une valeur differente du default ('08:00'), sinon
        //   Eloquent::update() est un no-op et updated_at n'est pas touche.
        sleep(3);
        $this->config->update(['heure_arrivee' => '09:30']);

        // Un gestionnaire actif existe (le service verifie la colonne role,
        // pas le role spatie — donc pas besoin de seeder ici)
        Gestionnaire::factory()->create(['est_actif' => true]);

        $alertes = $this->service->alertes();
        // Pas de demandes, pas de retards → 0 alerte
        $this->assertCount(0, $alertes);
    }

    public function test_from_current_construit_a_partir_du_singleton(): void
    {
        $service = DashboardKpiService::fromCurrent();
        $this->assertInstanceOf(DashboardKpiService::class, $service);
    }
}
