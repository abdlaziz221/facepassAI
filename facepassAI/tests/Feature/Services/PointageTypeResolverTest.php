<?php

namespace Tests\Feature\Services;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\PointageTypeResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du PointageTypeResolver (Sprint 4, US-033).
 *
 * Couvre tous les cas de la séquence : arrivée → début pause → fin pause → départ.
 */
class PointageTypeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected PointageTypeResolver $resolver;
    protected EmployeProfile $employe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->resolver = new PointageTypeResolver();
        $this->employe  = EmployeProfile::factory()->create();
    }

    // ============================================================
    // nextExpectedType()
    // ============================================================

    public function test_aucun_pointage_attend_arrivee(): void
    {
        $this->assertEquals(
            Pointage::TYPE_ARRIVEE,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    public function test_apres_arrivee_attend_debut_pause(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();

        $this->assertEquals(
            Pointage::TYPE_DEBUT_PAUSE,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    public function test_apres_debut_pause_attend_fin_pause(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        Pointage::factory()->for($this->employe, 'employe')->debutPause()->create();

        $this->assertEquals(
            Pointage::TYPE_FIN_PAUSE,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    public function test_apres_fin_pause_attend_depart(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        Pointage::factory()->for($this->employe, 'employe')->debutPause()->create();
        Pointage::factory()->for($this->employe, 'employe')->finPause()->create();

        $this->assertEquals(
            Pointage::TYPE_DEPART,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    public function test_apres_depart_retourne_null(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        Pointage::factory()->for($this->employe, 'employe')->debutPause()->create();
        Pointage::factory()->for($this->employe, 'employe')->finPause()->create();
        Pointage::factory()->for($this->employe, 'employe')->depart()->create();

        $this->assertNull($this->resolver->nextExpectedType($this->employe));
    }

    // ============================================================
    // Isolation par jour
    // ============================================================

    public function test_pointages_d_hier_sont_ignores(): void
    {
        // Hier l'employé a fait toute sa journée
        // ⚠ Eloquent traite created_at comme immutable apres insert,
        //   forceFill + save ne suffit pas. On passe par DB::table directement.
        $arr = Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        $dep = Pointage::factory()->for($this->employe, 'employe')->depart()->create();

        \Illuminate\Support\Facades\DB::table('pointages')
            ->where('id', $arr->id)
            ->update(['created_at' => now()->subDay()]);
        \Illuminate\Support\Facades\DB::table('pointages')
            ->where('id', $dep->id)
            ->update(['created_at' => now()->subDay()->addHours(8)]);

        // Aujourd'hui, on attend une nouvelle arrivée
        $this->assertEquals(
            Pointage::TYPE_ARRIVEE,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    public function test_pointages_d_un_autre_employe_sont_ignores(): void
    {
        $autre = EmployeProfile::factory()->create();
        Pointage::factory()->for($autre, 'employe')->arrivee()->create();

        // L'autre employé a fait son arrivée, mais celui-ci doit toujours faire la sienne
        $this->assertEquals(
            Pointage::TYPE_ARRIVEE,
            $this->resolver->nextExpectedType($this->employe)
        );
    }

    // ============================================================
    // isValidNext()
    // ============================================================

    public function test_is_valid_next_ok_pour_arrivee_en_debut_journee(): void
    {
        $this->assertTrue(
            $this->resolver->isValidNext($this->employe, Pointage::TYPE_ARRIVEE)
        );
    }

    public function test_is_valid_next_ko_pour_depart_en_debut_journee(): void
    {
        $this->assertFalse(
            $this->resolver->isValidNext($this->employe, Pointage::TYPE_DEPART)
        );
    }

    public function test_is_valid_next_ko_pour_arrivee_deja_faite(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();

        $this->assertFalse(
            $this->resolver->isValidNext($this->employe, Pointage::TYPE_ARRIVEE)
        );
    }

    public function test_is_valid_next_ok_apres_arrivee_pour_debut_pause(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();

        $this->assertTrue(
            $this->resolver->isValidNext($this->employe, Pointage::TYPE_DEBUT_PAUSE)
        );
    }

    public function test_is_valid_next_ko_apres_arrivee_pour_fin_pause_directe(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();

        $this->assertFalse(
            $this->resolver->isValidNext($this->employe, Pointage::TYPE_FIN_PAUSE)
        );
    }

    // ============================================================
    // dayCompleted()
    // ============================================================

    public function test_day_completed_false_en_debut_journee(): void
    {
        $this->assertFalse($this->resolver->dayCompleted($this->employe));
    }

    public function test_day_completed_false_apres_arrivee_uniquement(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        $this->assertFalse($this->resolver->dayCompleted($this->employe));
    }

    public function test_day_completed_true_apres_les_4_pointages(): void
    {
        Pointage::factory()->for($this->employe, 'employe')->arrivee()->create();
        Pointage::factory()->for($this->employe, 'employe')->debutPause()->create();
        Pointage::factory()->for($this->employe, 'employe')->finPause()->create();
        Pointage::factory()->for($this->employe, 'employe')->depart()->create();

        $this->assertTrue($this->resolver->dayCompleted($this->employe));
    }
}
