<?php

namespace Tests\Feature\Models;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du modèle Pointage et de sa relation avec EmployeProfile (US-032).
 */
class PointageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_un_pointage_appartient_a_un_employe(): void
    {
        $profile  = EmployeProfile::factory()->create();
        $pointage = Pointage::factory()->for($profile, 'employe')->create();

        $this->assertInstanceOf(EmployeProfile::class, $pointage->employe);
        $this->assertEquals($profile->id, $pointage->employe->id);
    }

    public function test_factory_par_defaut_genere_un_type_valide(): void
    {
        $pointage = Pointage::factory()->create();

        $this->assertContains($pointage->type, Pointage::TYPES);
        $this->assertFalse($pointage->manuel);
        $this->assertNull($pointage->motif_manuel);
        $this->assertNull($pointage->photo_capture);
    }

    public function test_state_arrivee_force_le_type(): void
    {
        $pointage = Pointage::factory()->arrivee()->create();
        $this->assertEquals(Pointage::TYPE_ARRIVEE, $pointage->type);
    }

    public function test_state_depart_force_le_type(): void
    {
        $pointage = Pointage::factory()->depart()->create();
        $this->assertEquals(Pointage::TYPE_DEPART, $pointage->type);
    }

    public function test_state_debut_pause_force_le_type(): void
    {
        $pointage = Pointage::factory()->debutPause()->create();
        $this->assertEquals(Pointage::TYPE_DEBUT_PAUSE, $pointage->type);
    }

    public function test_state_fin_pause_force_le_type(): void
    {
        $pointage = Pointage::factory()->finPause()->create();
        $this->assertEquals(Pointage::TYPE_FIN_PAUSE, $pointage->type);
    }

    public function test_state_manuel_active_le_flag_et_le_motif(): void
    {
        $pointage = Pointage::factory()->manuel('Caméra HS')->create();

        $this->assertTrue($pointage->manuel);
        $this->assertEquals('Caméra HS', $pointage->motif_manuel);
    }

    public function test_manuel_cast_en_booleen(): void
    {
        $pointage = Pointage::factory()->create(['manuel' => 1]);
        $this->assertIsBool($pointage->manuel);
        $this->assertTrue($pointage->manuel);
    }

    public function test_un_employe_peut_avoir_plusieurs_pointages(): void
    {
        $profile = EmployeProfile::factory()->create();

        Pointage::factory()->count(4)->for($profile, 'employe')->sequence(
            ['type' => Pointage::TYPE_ARRIVEE],
            ['type' => Pointage::TYPE_DEBUT_PAUSE],
            ['type' => Pointage::TYPE_FIN_PAUSE],
            ['type' => Pointage::TYPE_DEPART],
        )->create();

        $this->assertEquals(4, Pointage::where('employe_id', $profile->id)->count());
    }

    public function test_type_invalide_rejete_par_la_base_de_donnees(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Pointage::factory()->for($profile, 'employe')->create(['type' => 'type_inexistant']);
    }

    public function test_constantes_de_type_exposees(): void
    {
        $this->assertEquals('arrivee', Pointage::TYPE_ARRIVEE);
        $this->assertEquals('debut_pause', Pointage::TYPE_DEBUT_PAUSE);
        $this->assertEquals('fin_pause', Pointage::TYPE_FIN_PAUSE);
        $this->assertEquals('depart', Pointage::TYPE_DEPART);
        $this->assertCount(4, Pointage::TYPES);
    }
}
