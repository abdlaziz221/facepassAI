<?php

namespace Tests\Feature\Services;

use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\JourFerie;
use App\Models\JoursTravail;
use App\Models\Pointage;
use App\Services\PayrollService;
use App\Services\RetardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 6 carte 1 (US-081) — Tests exhaustifs du PayrollService.
 *
 * Config par défaut : 08:00 / 12:00 / 13:00 / 17:00 → 8h effectives/jour.
 * Jours ouvrables par défaut : lun-ven.
 */
class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JoursTravail $config;
    protected PayrollService $service;
    protected EmployeProfile $emp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config  = JoursTravail::current();
        $this->service = new PayrollService(new RetardService($this->config), $this->config);

        $this->emp = EmployeProfile::factory()->create([
            'salaire_brut' => 440000,
        ]);

        // Pour stabiliser les tests "absences", on figue aujourd'hui à fin du mois testé
        Carbon::setTestNow(Carbon::parse('2026-06-30 23:59:59'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function makePointage(string $type, string $datetime): Pointage
    {
        $p = Pointage::factory()->for($this->emp, 'employe')->create(['type' => $type]);
        $p->forceFill(['created_at' => $datetime])->save();
        return $p;
    }

    // ========================================================================
    // calculerSalaireBrut
    // ========================================================================

    public function test_brut_renvoie_le_salaire_du_profil(): void
    {
        $this->assertEquals(440000, $this->service->calculerSalaireBrut($this->emp));
    }

    public function test_brut_renvoie_zero_si_pas_de_salaire(): void
    {
        $sans = EmployeProfile::factory()->create(['salaire_brut' => 0]);
        $this->assertEquals(0, $this->service->calculerSalaireBrut($sans));
    }

    // ========================================================================
    // Helpers heuresParJour / joursOuvrables
    // ========================================================================

    public function test_heures_par_jour_par_defaut_est_huit(): void
    {
        // 08:00 → 17:00 = 9h brut - 1h pause = 8h net
        $this->assertEquals(8.0, $this->service->heuresParJourTheoriques());
    }

    public function test_jours_ouvrables_juin_2026(): void
    {
        // Juin 2026 : 22 jours ouvrables (lun-ven), sans jour férié configuré
        $jours = $this->service->joursOuvrablesDuMois(2026, 6);
        $this->assertEquals(22, count($jours));
    }

    public function test_jours_ouvrables_exclut_les_jours_feries(): void
    {
        JourFerie::create(['date' => '2026-06-10', 'libelle' => 'Test férié']);
        $this->assertEquals(21, count($this->service->joursOuvrablesDuMois(2026, 6)));
    }

    // ========================================================================
    // calculerDeductions — retards
    // ========================================================================

    public function test_deduction_retards_compte_les_minutes_apres_l_heure(): void
    {
        // 15 minutes de retard sur l'arrivée
        $this->makePointage('arrivee', '2026-06-10 08:15:00');

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(15, $d['retards']['minutes']);
        $this->assertGreaterThan(0, $d['retards']['montant']);
    }

    public function test_deduction_retards_pas_de_retard_si_a_l_heure(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(0, $d['retards']['minutes']);
        $this->assertEquals(0, $d['retards']['montant']);
    }

    public function test_deduction_retards_cumule_plusieurs_pointages(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:15:00'); // +15
        $this->makePointage('arrivee', '2026-06-11 08:30:00'); // +30
        $this->makePointage('fin_pause', '2026-06-10 13:10:00'); // +10

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(55, $d['retards']['minutes']);
    }

    // ========================================================================
    // calculerDeductions — départs anticipés
    // ========================================================================

    public function test_deduction_departs_anticipes(): void
    {
        $this->makePointage('depart', '2026-06-10 16:30:00'); // 30 min avant 17h

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(30, $d['departs_anticipes']['minutes']);
        $this->assertGreaterThan(0, $d['departs_anticipes']['montant']);
    }

    public function test_deduction_pas_de_depart_anticipe_si_heures_sup(): void
    {
        $this->makePointage('depart', '2026-06-10 18:00:00'); // 1h de plus

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(0, $d['departs_anticipes']['minutes']);
    }

    // ========================================================================
    // calculerDeductions — absences
    // ========================================================================

    public function test_deduction_absences_jour_ouvrable_non_pointe_compte_comme_absent(): void
    {
        // Aucun pointage tout le mois → 22 jours d'absence
        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(22, $d['absences']['jours']);
    }

    public function test_deduction_absences_exclut_les_jours_pointes(): void
    {
        // Pointe le 10 juin → 21 absences (22 - 1)
        $this->makePointage('arrivee', '2026-06-10 08:00:00');

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(21, $d['absences']['jours']);
    }

    public function test_deduction_absences_exclut_les_jours_couverts_par_demande_validee(): void
    {
        DemandeAbsence::factory()->for($this->emp, 'employe')->create([
            'date_debut' => '2026-06-08',
            'date_fin'   => '2026-06-12',
            'statut'     => DemandeAbsence::STATUT_VALIDEE,
        ]);

        // 5 jours de congé validé (lun-ven du 8 au 12) → 22 - 5 = 17 jours d'absence
        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(17, $d['absences']['jours']);
    }

    public function test_deduction_absences_n_exclut_pas_les_demandes_en_attente(): void
    {
        DemandeAbsence::factory()->for($this->emp, 'employe')->create([
            'date_debut' => '2026-06-08',
            'date_fin'   => '2026-06-12',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Demande non validée → ne couvre pas → 22 jours d'absence
        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $this->assertEquals(22, $d['absences']['jours']);
    }

    public function test_deduction_absences_exclut_les_jours_feries(): void
    {
        JourFerie::create(['date' => '2026-06-10', 'libelle' => 'Férié']);

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        // 22 jours ouvrables - 1 férié = 21 jours possibles
        $this->assertEquals(21, $d['absences']['jours']);
    }

    public function test_deduction_absences_exclut_les_jours_futurs(): void
    {
        // Aujourd'hui = 15 juin
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        // Du 1er au 15 juin : 11 jours ouvrables (lun-ven), tous absents
        $this->assertLessThanOrEqual(11, $d['absences']['jours']);
        $this->assertGreaterThan(0, $d['absences']['jours']);
    }

    // ========================================================================
    // calculerDeductions — total + meta
    // ========================================================================

    public function test_deductions_total_somme_les_trois_postes(): void
    {
        $this->makePointage('arrivee', '2026-06-10 08:15:00'); // retard 15 min

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        $somme = $d['retards']['montant'] + $d['departs_anticipes']['montant'] + $d['absences']['montant'];

        $this->assertEqualsWithDelta($somme, $d['total'], 0.01);
    }

    public function test_deductions_meta_contient_les_taux(): void
    {
        $d = $this->service->calculerDeductions($this->emp, 2026, 6);

        $this->assertEquals(22, $d['meta']['jours_ouvrables_mois']);
        $this->assertEquals(8.0, $d['meta']['heures_par_jour']);
        $this->assertEquals(176, $d['meta']['heures_mois']);
        // 440000 / 176 = 2500 F/h
        $this->assertEquals(2500.0, $d['meta']['tarif_horaire']);
        // 2500 × 8 = 20000 F/jour
        $this->assertEquals(20000.0, $d['meta']['tarif_journalier']);
    }

    // ========================================================================
    // calculerNet
    // ========================================================================

    public function test_net_egale_brut_sans_deductions(): void
    {
        // Aucune absence : il faut pointer tous les jours
        $jours = $this->service->joursOuvrablesDuMois(2026, 6);
        foreach ($jours as $j) {
            $this->makePointage('arrivee', $j->format('Y-m-d') . ' 08:00:00');
        }

        $this->assertEquals(440000, $this->service->calculerNet($this->emp, 2026, 6));
    }

    public function test_net_jamais_negatif(): void
    {
        // Pas de pointage du tout → 22 jours d'absence
        // 22 × 20000 = 440000 → exactement le brut, net = 0
        // Si on a aussi des retards, ça déduirait plus → net devrait rester 0
        $this->makePointage('arrivee', '2026-06-30 12:00:00'); // énorme retard

        $net = $this->service->calculerNet($this->emp, 2026, 6);
        $this->assertGreaterThanOrEqual(0, $net);
    }

    public function test_net_avec_retards_seulement(): void
    {
        // On pointe tous les jours pour éviter les absences
        $jours = $this->service->joursOuvrablesDuMois(2026, 6);
        foreach ($jours as $j) {
            $this->makePointage('arrivee', $j->format('Y-m-d') . ' 08:00:00');
        }
        // Ajoute 30 minutes de retard sur un des pointages
        $this->makePointage('arrivee', '2026-06-15 08:30:00');

        $d = $this->service->calculerDeductions($this->emp, 2026, 6);
        // tarif_minute = 2500/60 ≈ 41.67, 30 min ≈ 1250 F
        $this->assertGreaterThan(1000, $d['retards']['montant']);
        $this->assertLessThan(1500, $d['retards']['montant']);
    }

    // ========================================================================
    // calculerSalaireMensuel — DTO complet
    // ========================================================================

    public function test_salaire_mensuel_renvoie_la_structure_complete(): void
    {
        $r = $this->service->calculerSalaireMensuel($this->emp, 2026, 6);

        $this->assertArrayHasKey('year', $r);
        $this->assertArrayHasKey('month', $r);
        $this->assertArrayHasKey('brut', $r);
        $this->assertArrayHasKey('deductions', $r);
        $this->assertArrayHasKey('net', $r);
        $this->assertEquals(2026, $r['year']);
        $this->assertEquals(6, $r['month']);
    }

    // ========================================================================
    // Factory
    // ========================================================================

    public function test_from_current_construit_a_partir_du_singleton(): void
    {
        $service = PayrollService::fromCurrent();
        $this->assertInstanceOf(PayrollService::class, $service);
        $this->assertEquals(8.0, $service->heuresParJourTheoriques());
    }
}
