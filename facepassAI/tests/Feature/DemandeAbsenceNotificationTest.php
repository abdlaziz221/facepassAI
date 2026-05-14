<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Notifications\NouvelleDemandeAbsenceNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests de la notification aux gestionnaires (Sprint 4 Horaires carte 9, US-050).
 */
class DemandeAbsenceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeEmployeWithProfile(): Employe
    {
        $employe = Employe::factory()->create();
        $employe->assignRole(Role::Employe->value);
        EmployeProfile::factory()->create(['user_id' => $employe->id]);
        return $employe;
    }

    protected function makeGestionnaire(): Gestionnaire
    {
        $g = Gestionnaire::factory()->create();
        $g->assignRole(Role::Gestionnaire->value);
        return $g;
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date_debut' => now()->addDays(5)->format('Y-m-d'),
            'date_fin'   => now()->addDays(10)->format('Y-m-d'),
            'motif'      => 'Congé annuel de printemps',
        ], $overrides);
    }

    // ============================================================
    // Envoi de la notification
    // ============================================================

    public function test_notification_envoyee_a_tous_les_gestionnaires(): void
    {
        Notification::fake();

        $employe        = $this->makeEmployeWithProfile();
        $gestionnaire1  = $this->makeGestionnaire();
        $gestionnaire2  = $this->makeGestionnaire();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        Notification::assertSentTo($gestionnaire1, NouvelleDemandeAbsenceNotification::class);
        Notification::assertSentTo($gestionnaire2, NouvelleDemandeAbsenceNotification::class);
    }

    public function test_notification_pas_envoyee_aux_autres_roles(): void
    {
        Notification::fake();

        $employe = $this->makeEmployeWithProfile();

        $admin = Administrateur::factory()->create();
        $admin->assignRole(Role::Administrateur->value);

        $consultant = Consultant::factory()->create();
        $consultant->assignRole(Role::Consultant->value);

        $this->makeGestionnaire(); // Au moins un gestionnaire à notifier

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        Notification::assertNotSentTo($admin, NouvelleDemandeAbsenceNotification::class);
        Notification::assertNotSentTo($consultant, NouvelleDemandeAbsenceNotification::class);
        Notification::assertNotSentTo($employe, NouvelleDemandeAbsenceNotification::class);
    }

    public function test_aucune_notification_si_aucun_gestionnaire(): void
    {
        Notification::fake();

        $employe = $this->makeEmployeWithProfile();
        // Pas de gestionnaire dans le système

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload())
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
        // Mais la demande est bien créée
        $this->assertEquals(1, DemandeAbsence::count());
    }

    // ============================================================
    // Canaux
    // ============================================================

    public function test_canaux_utilises_sont_database_et_mail(): void
    {
        $demande = DemandeAbsence::factory()->create();
        $notif   = new NouvelleDemandeAbsenceNotification($demande);

        $canaux = $notif->via(new \stdClass());

        $this->assertContains('database', $canaux);
        $this->assertContains('mail', $canaux);
    }

    // ============================================================
    // Contenu — database
    // ============================================================

    public function test_to_database_contient_les_donnees_attendues(): void
    {
        $profile = EmployeProfile::factory()->create();
        $demande = DemandeAbsence::factory()->for($profile, 'employe')->create([
            'motif' => 'Mariage',
            'date_debut' => '2026-06-15',
            'date_fin'   => '2026-06-20',
        ]);

        $notif = new NouvelleDemandeAbsenceNotification($demande);
        $data  = $notif->toDatabase(new \stdClass());

        $this->assertEquals($demande->id, $data['demande_id']);
        $this->assertEquals($profile->id, $data['employe_id']);
        $this->assertEquals('Mariage', $data['motif']);
        $this->assertEquals('2026-06-15', $data['date_debut']);
        $this->assertEquals('2026-06-20', $data['date_fin']);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('15/06/2026', $data['message']);
        $this->assertStringContainsString('20/06/2026', $data['message']);
    }

    // ============================================================
    // Contenu — mail
    // ============================================================

    public function test_to_mail_contient_le_nom_et_les_dates(): void
    {
        $employe = $this->makeEmployeWithProfile();
        $employe->name = 'Aïssatou Diop';
        $employe->save();

        $profile = EmployeProfile::where('user_id', $employe->id)->first();
        $demande = DemandeAbsence::factory()->for($profile, 'employe')->create([
            'motif' => 'Vacances en famille',
            'date_debut' => '2026-07-10',
            'date_fin'   => '2026-07-25',
        ]);

        $notif   = new NouvelleDemandeAbsenceNotification($demande);
        $mail    = $notif->toMail($this->makeGestionnaire());
        $array   = $mail->toArray();
        $json    = json_encode($array, JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('Aïssatou Diop', $mail->subject);
        $this->assertStringContainsString('10/07/2026', $json);
        $this->assertStringContainsString('25/07/2026', $json);
        $this->assertStringContainsString('Vacances en famille', $json);
    }

    // ============================================================
    // Canal database — réel (sans fake)
    // ============================================================

    public function test_canal_database_cree_une_ligne_pour_chaque_gestionnaire(): void
    {
        $employe       = $this->makeEmployeWithProfile();
        $gestionnaire  = $this->makeGestionnaire();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        $this->assertEquals(1, $gestionnaire->notifications()->count());

        $notif = $gestionnaire->notifications()->first();
        $this->assertEquals(NouvelleDemandeAbsenceNotification::class, $notif->type);
        $this->assertArrayHasKey('demande_id', $notif->data);
    }

    public function test_notification_creee_est_non_lue_initialement(): void
    {
        $employe       = $this->makeEmployeWithProfile();
        $gestionnaire  = $this->makeGestionnaire();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        $this->assertEquals(1, $gestionnaire->unreadNotifications()->count());
    }

    // ============================================================
    // Mark as read
    // ============================================================

    public function test_gestionnaire_peut_marquer_toutes_les_notifs_comme_lues(): void
    {
        $employe       = $this->makeEmployeWithProfile();
        $gestionnaire  = $this->makeGestionnaire();

        $this->actingAs($employe)->post('/demandes-absence', $this->validPayload());
        $this->actingAs($employe)->post('/demandes-absence', $this->validPayload([
            'date_debut' => now()->addDays(20)->format('Y-m-d'),
            'date_fin'   => now()->addDays(25)->format('Y-m-d'),
        ]));

        $this->assertEquals(2, $gestionnaire->unreadNotifications()->count());

        $this->actingAs($gestionnaire)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertEquals(0, $gestionnaire->fresh()->unreadNotifications()->count());
    }

    public function test_gestionnaire_peut_marquer_une_notif_comme_lue(): void
    {
        $employe       = $this->makeEmployeWithProfile();
        $gestionnaire  = $this->makeGestionnaire();

        $this->actingAs($employe)->post('/demandes-absence', $this->validPayload());

        $notif = $gestionnaire->notifications()->first();
        $this->assertNull($notif->read_at);

        $this->actingAs($gestionnaire)
            ->post("/notifications/{$notif->id}/read")
            ->assertRedirect();

        $this->assertNotNull($notif->fresh()->read_at);
    }

    // ============================================================
    // API summary
    // ============================================================

    public function test_endpoint_summary_retourne_le_compteur(): void
    {
        $employe       = $this->makeEmployeWithProfile();
        $gestionnaire  = $this->makeGestionnaire();

        $this->actingAs($employe)->post('/demandes-absence', $this->validPayload());

        $this->actingAs($gestionnaire)
            ->getJson('/notifications/summary')
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 1);
    }
}
