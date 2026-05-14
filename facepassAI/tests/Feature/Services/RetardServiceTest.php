<?php

namespace Tests\Feature\Services;

use App\Models\EmployeProfile;
use App\Models\JoursTravail;
use App\Models\Pointage;
use App\Services\RetardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 3 (US-062) — Tests exhaustifs du RetardService.
 *
 * Convention de signe :
 *   + = heure réelle APRÈS l'heure théorique
 *   - = heure réelle AVANT l'heure théorique
 */
class RetardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JoursTravail $config;
    protected RetardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Config par défaut : 08:00 / 12:00 / 13:00 / 17:00
        $this->config  = JoursTravail::current();
        $this->service = new RetardService($this->config);
    }

    // ========================================================================
    // heureTheoriquePour() — mapping des 4 types
    // ========================================================================

    public function test_heure_theorique_pour_arrivee(): void
    {
        $this->assertEquals('08:00', $this->service->heureTheoriquePour('arrivee'));
    }

    public function test_heure_theorique_pour_debut_pause(): void
    {
        $this->assertEquals('12:00', $this->service->heureTheoriquePour('debut_pause'));
    }

    public function test_heure_theorique_pour_fin_pause(): void
    {
        $this->assertEquals('13:00', $this->service->heureTheoriquePour('fin_pause'));
    }

    public function test_heure_theorique_pour_depart(): void
    {
        $this->assertEquals('17:00', $this->service->heureTheoriquePour('depart'));
    }

    public function test_heure_theorique_pour_type_inconnu_null(): void
    {
        $this->assertNull($this->service->heureTheoriquePour('inconnu'));
    }

    // ========================================================================
    // ecartEnMinutes() — Arrivée
    // ========================================================================

    public function test_ecart_arrivee_a_l_heure_pile(): void
    {
        $heure = Carbon::parse('2026-06-10 08:00:00');
        $this->assertEquals(0, $this->service->ecartEnMinutes('arrivee', $heure));
    }

    public function test_ecart_arrivee_en_retard_15_min(): void
    {
        $heure = Carbon::parse('2026-06-10 08:15:00');
        $this->assertEquals(15, $this->service->ecartEnMinutes('arrivee', $heure));
    }

    public function test_ecart_arrivee_en_retard_2h(): void
    {
        $heure = Carbon::parse('2026-06-10 10:00:00');
        $this->assertEquals(120, $this->service->ecartEnMinutes('arrivee', $heure));
    }

    public function test_ecart_arrivee_en_avance_negatif(): void
    {
        $heure = Carbon::parse('2026-06-10 07:45:00');
        $this->assertEquals(-15, $this->service->ecartEnMinutes('arrivee', $heure));
    }

    // ========================================================================
    // ecartEnMinutes() — Départ
    // ========================================================================

    public function test_ecart_depart_a_l_heure_pile(): void
    {
        $heure = Carbon::parse('2026-06-10 17:00:00');
        $this->assertEquals(0, $this->service->ecartEnMinutes('depart', $heure));
    }

    public function test_ecart_depart_anticipe_negatif(): void
    {
        $heure = Carbon::parse('2026-06-10 16:30:00');
        $this->assertEquals(-30, $this->service->ecartEnMinutes('depart', $heure));
    }

    public function test_ecart_depart_heures_sup_positif(): void
    {
        $heure = Carbon::parse('2026-06-10 18:15:00');
        $this->assertEquals(75, $this->service->ecartEnMinutes('depart', $heure));
    }

    // ========================================================================
    // ecartEnMinutes() — Pauses
    // ========================================================================

    public function test_ecart_debut_pause_a_l_heure(): void
    {
        $heure = Carbon::parse('2026-06-10 12:00:00');
        $this->assertEquals(0, $this->service->ecartEnMinutes('debut_pause', $heure));
    }

    public function test_ecart_fin_pause_retour_en_retard(): void
    {
        $heure = Carbon::parse('2026-06-10 13:20:00');
        $this->assertEquals(20, $this->service->ecartEnMinutes('fin_pause', $heure));
    }

    public function test_ecart_type_inconnu_renvoie_zero(): void
    {
        $heure = Carbon::parse('2026-06-10 10:00:00');
        $this->assertEquals(0, $this->service->ecartEnMinutes('inconnu', $heure));
    }

    // ========================================================================
    // isRetard() — arrivée et fin de pause
    // ========================================================================

    public function test_is_retard_arrivee_apres_l_heure(): void
    {
        $this->assertTrue($this->service->isRetard('arrivee', Carbon::parse('2026-06-10 08:01:00')));
    }

    public function test_is_retard_arrivee_a_l_heure_pile_pas_retard(): void
    {
        $this->assertFalse($this->service->isRetard('arrivee', Carbon::parse('2026-06-10 08:00:00')));
    }

    public function test_is_retard_arrivee_en_avance_pas_retard(): void
    {
        $this->assertFalse($this->service->isRetard('arrivee', Carbon::parse('2026-06-10 07:30:00')));
    }

    public function test_is_retard_fin_pause_retour_tardif(): void
    {
        $this->assertTrue($this->service->isRetard('fin_pause', Carbon::parse('2026-06-10 13:10:00')));
    }

    public function test_is_retard_depart_jamais_meme_si_tard(): void
    {
        // Partir tard, ce sont des heures sup, pas un retard
        $this->assertFalse($this->service->isRetard('depart', Carbon::parse('2026-06-10 19:00:00')));
    }

    public function test_is_retard_debut_pause_jamais(): void
    {
        $this->assertFalse($this->service->isRetard('debut_pause', Carbon::parse('2026-06-10 12:30:00')));
    }

    // ========================================================================
    // isDepartAnticipe() — départ et début de pause
    // ========================================================================

    public function test_is_depart_anticipe_depart_avant_l_heure(): void
    {
        $this->assertTrue($this->service->isDepartAnticipe('depart', Carbon::parse('2026-06-10 16:30:00')));
    }

    public function test_is_depart_anticipe_depart_a_l_heure_pile_pas_anticipe(): void
    {
        $this->assertFalse($this->service->isDepartAnticipe('depart', Carbon::parse('2026-06-10 17:00:00')));
    }

    public function test_is_depart_anticipe_depart_apres_l_heure_pas_anticipe(): void
    {
        $this->assertFalse($this->service->isDepartAnticipe('depart', Carbon::parse('2026-06-10 17:30:00')));
    }

    public function test_is_depart_anticipe_debut_pause_anticipee(): void
    {
        $this->assertTrue($this->service->isDepartAnticipe('debut_pause', Carbon::parse('2026-06-10 11:30:00')));
    }

    public function test_is_depart_anticipe_arrivee_jamais(): void
    {
        $this->assertFalse($this->service->isDepartAnticipe('arrivee', Carbon::parse('2026-06-10 07:00:00')));
    }

    // ========================================================================
    // isATemps() — avec et sans tolérance
    // ========================================================================

    public function test_is_a_temps_pile_a_l_heure(): void
    {
        $this->assertTrue($this->service->isATemps('arrivee', Carbon::parse('2026-06-10 08:00:00')));
    }

    public function test_is_a_temps_dans_la_tolerance_5_min(): void
    {
        // 3 minutes de retard, tolérance 5
        $this->assertTrue($this->service->isATemps('arrivee', Carbon::parse('2026-06-10 08:03:00'), 5));
    }

    public function test_is_a_temps_hors_tolerance(): void
    {
        // 10 minutes de retard, tolérance 5
        $this->assertFalse($this->service->isATemps('arrivee', Carbon::parse('2026-06-10 08:10:00'), 5));
    }

    public function test_is_a_temps_type_sans_reference_toujours_vrai(): void
    {
        $this->assertTrue($this->service->isATemps('inconnu', Carbon::parse('2026-06-10 03:00:00')));
    }

    // ========================================================================
    // analyserPointage() — analyse complète d'un Pointage Eloquent
    // ========================================================================

    public function test_analyser_pointage_arrivee_en_retard(): void
    {
        $profile = EmployeProfile::factory()->create();
        $p = Pointage::factory()->for($profile, 'employe')->create(['type' => 'arrivee']);
        $p->forceFill(['created_at' => '2026-06-10 08:25:00'])->save();
        $p->refresh();

        $analyse = $this->service->analyserPointage($p);

        $this->assertEquals('arrivee', $analyse['type']);
        $this->assertEquals('08:25', $analyse['heure_reelle']);
        $this->assertEquals('08:00', $analyse['heure_theorique']);
        $this->assertEquals(25, $analyse['ecart_minutes']);
        $this->assertTrue($analyse['is_retard']);
        $this->assertFalse($analyse['is_depart_anticipe']);
        $this->assertFalse($analyse['is_a_temps']);
    }

    public function test_analyser_pointage_depart_anticipe(): void
    {
        $profile = EmployeProfile::factory()->create();
        $p = Pointage::factory()->for($profile, 'employe')->create(['type' => 'depart']);
        $p->forceFill(['created_at' => '2026-06-10 16:30:00'])->save();
        $p->refresh();

        $analyse = $this->service->analyserPointage($p);

        $this->assertEquals(-30, $analyse['ecart_minutes']);
        $this->assertFalse($analyse['is_retard']);
        $this->assertTrue($analyse['is_depart_anticipe']);
    }

    public function test_analyser_pointage_a_temps(): void
    {
        $profile = EmployeProfile::factory()->create();
        $p = Pointage::factory()->for($profile, 'employe')->create(['type' => 'arrivee']);
        $p->forceFill(['created_at' => '2026-06-10 08:00:00'])->save();
        $p->refresh();

        $analyse = $this->service->analyserPointage($p);

        $this->assertEquals(0, $analyse['ecart_minutes']);
        $this->assertFalse($analyse['is_retard']);
        $this->assertTrue($analyse['is_a_temps']);
    }

    // ========================================================================
    // Configuration personnalisée
    // ========================================================================

    public function test_service_respecte_la_configuration_personnalisee(): void
    {
        $this->config->update([
            'heure_arrivee' => '09:30',
            'heure_depart'  => '18:00',
        ]);
        $service = new RetardService($this->config->refresh());

        $this->assertEquals('09:30', $service->heureTheoriquePour('arrivee'));
        $this->assertEquals(30, $service->ecartEnMinutes('arrivee', Carbon::parse('2026-06-10 10:00:00')));
    }

    public function test_from_current_construit_a_partir_du_singleton(): void
    {
        $service = RetardService::fromCurrent();
        $this->assertInstanceOf(RetardService::class, $service);
        $this->assertEquals('08:00', $service->heureTheoriquePour('arrivee'));
    }
}
