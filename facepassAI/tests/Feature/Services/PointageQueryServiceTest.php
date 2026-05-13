<?php

namespace Tests\Feature\Services;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\PointageQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 1 (US-060) — Tests unitaires du service de requête.
 */
class PointageQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PointageQueryService $service;
    protected EmployeProfile $emp1;
    protected EmployeProfile $emp2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PointageQueryService::class);
        $this->emp1 = EmployeProfile::factory()->create();
        $this->emp2 = EmployeProfile::factory()->create();
    }

    protected function makePointage(EmployeProfile $emp, string $type, string $datetime, bool $manuel = false): Pointage
    {
        $p = Pointage::factory()->for($emp, 'employe')->create([
            'type'   => $type,
            'manuel' => $manuel,
        ]);
        // On force created_at car la factory met now()
        $p->forceFill(['created_at' => $datetime])->save();
        return $p;
    }

    // ========================================================================
    // Sans filtres : tout sort
    // ========================================================================

    public function test_sans_filtre_retourne_tout(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp2, 'depart',  '2026-06-10 17:00:00');

        $this->assertEquals(2, $this->service->query()->count());
    }

    // ========================================================================
    // Filtre employe_id
    // ========================================================================

    public function test_filtre_employe_id(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00');

        $results = $this->service->query(['employe_id' => $this->emp1->id])->get();

        $this->assertCount(2, $results);
        foreach ($results as $p) {
            $this->assertEquals($this->emp1->id, $p->employe_id);
        }
    }

    // ========================================================================
    // Filtres date / date_from / date_to
    // ========================================================================

    public function test_filtre_date_exacte(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');

        $this->assertEquals(2, $this->service->query(['date' => '2026-06-10'])->count());
    }

    public function test_filtre_date_from(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-09 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');

        $this->assertEquals(2, $this->service->query(['date_from' => '2026-06-10'])->count());
    }

    public function test_filtre_date_to(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-09 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');

        $this->assertEquals(2, $this->service->query(['date_to' => '2026-06-10'])->count());
    }

    public function test_filtre_intervalle_date_from_et_date_to(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-09 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-12 08:00:00');

        $count = $this->service->query([
            'date_from' => '2026-06-10',
            'date_to'   => '2026-06-11',
        ])->count();

        $this->assertEquals(2, $count);
    }

    public function test_date_exacte_prioritaire_sur_date_from_to(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-09 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');

        $count = $this->service->query([
            'date'      => '2026-06-10',
            'date_from' => '2026-06-09',  // ignoré
            'date_to'   => '2026-06-11',  // ignoré
        ])->count();

        $this->assertEquals(1, $count);
    }

    // ========================================================================
    // Filtre type
    // ========================================================================

    public function test_filtre_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');

        $this->assertEquals(2, $this->service->query(['type' => 'arrivee'])->count());
        $this->assertEquals(1, $this->service->query(['type' => 'depart'])->count());
    }

    public function test_filtre_type_invalide_ignore(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');

        // Type bidon → on retourne tout sans appliquer le filtre
        $this->assertEquals(2, $this->service->query(['type' => 'pwned'])->count());
    }

    // ========================================================================
    // Filtre manuel
    // ========================================================================

    public function test_filtre_manuel_true(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00', true);
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00', false);

        $this->assertEquals(1, $this->service->query(['manuel' => true])->count());
    }

    public function test_filtre_manuel_false(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00', true);
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00', false);

        $this->assertEquals(1, $this->service->query(['manuel' => false])->count());
    }

    // ========================================================================
    // Filtres combinés
    // ========================================================================

    public function test_filtres_combines_employe_date_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00'); // ✓
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00'); // ✗ type
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00'); // ✗ date
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00'); // ✗ emp

        $count = $this->service->query([
            'employe_id' => $this->emp1->id,
            'date'       => '2026-06-10',
            'type'       => 'arrivee',
        ])->count();

        $this->assertEquals(1, $count);
    }

    // ========================================================================
    // Tri + Pagination
    // ========================================================================

    public function test_pagine_par_20_par_defaut(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makePointage($this->emp1, 'arrivee', '2026-06-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ' 08:00:00');
        }

        $page = $this->service->paginate();
        $this->assertEquals(20, $page->count());
        $this->assertEquals(25, $page->total());
    }

    public function test_tri_par_date_desc_par_defaut(): void
    {
        $vieux  = $this->makePointage($this->emp1, 'arrivee', '2026-06-01 08:00:00');
        $recent = $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $milieu = $this->makePointage($this->emp1, 'arrivee', '2026-06-05 08:00:00');

        $page = $this->service->paginate();
        $ids = $page->getCollection()->pluck('id')->all();

        $this->assertEquals([$recent->id, $milieu->id, $vieux->id], $ids);
    }

    public function test_tri_par_date_asc(): void
    {
        $vieux  = $this->makePointage($this->emp1, 'arrivee', '2026-06-01 08:00:00');
        $recent = $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $page = $this->service->paginate([], 'created_at', 'asc');
        $ids = $page->getCollection()->pluck('id')->all();

        $this->assertEquals([$vieux->id, $recent->id], $ids);
    }

    public function test_tri_par_colonne_non_autorisee_fallback_sur_created_at(): void
    {
        // Pas d'erreur — fallback silencieux sur created_at
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $page = $this->service->paginate([], 'password', 'desc');
        $this->assertEquals(1, $page->count());
    }

    // ========================================================================
    // Compteurs par type
    // ========================================================================

    public function test_counts_by_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee',     '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee',     '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'debut_pause', '2026-06-10 12:00:00');
        $this->makePointage($this->emp1, 'fin_pause',   '2026-06-10 13:00:00');
        $this->makePointage($this->emp1, 'depart',      '2026-06-10 17:00:00');
        $this->makePointage($this->emp1, 'depart',      '2026-06-11 17:00:00');
        $this->makePointage($this->emp1, 'depart',      '2026-06-12 17:00:00');

        $counts = $this->service->countsByType();

        $this->assertEquals(2, $counts['arrivee']);
        $this->assertEquals(1, $counts['debut_pause']);
        $this->assertEquals(1, $counts['fin_pause']);
        $this->assertEquals(3, $counts['depart']);
    }

    public function test_counts_by_type_respecte_les_filtres(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00');

        $counts = $this->service->countsByType(['employe_id' => $this->emp1->id]);

        $this->assertEquals(1, $counts['arrivee']);
    }
}
