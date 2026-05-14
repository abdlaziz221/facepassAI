<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Notifications\DemandeAbsenceTraiteeNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sprint 4 Horaires carte 11 (US-053)
 * Notification envoyée à l'employé quand sa demande d'absence
 * est validée ou refusée.
 */
class DemandeAbsenceTraiteeNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Gestionnaire $gestionnaire;
    protected Employe $employe;
    protected EmployeProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gestionnaire = Gestionnaire::factory()->create(['name' => 'Marie Diop']);
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->employe = Employe::factory()->create(['name' => 'Aïssatou Sow']);
        $this->employe->assignRole(Role::Employe->value);
        $this->profile = EmployeProfile::factory()->create(['user_id' => $this->employe->id]);
    }

    protected function makeDemande(array $attrs = []): DemandeAbsence
    {
        return DemandeAbsence::factory()->for($this->profile, 'employe')->create(array_merge([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'motif'      => 'Congés annuels',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ], $attrs));
    }

    // ========================================================================
    // Déclenchement via le contrôleur
    // ========================================================================

    public function test_valider_envoie_une_notification_a_l_employe(): void
    {
        Notification::fake();
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande), [
                'commentaire' => 'Bonnes vacances',
            ]);

        Notification::assertSentTo($this->employe, DemandeAbsenceTraiteeNotification::class);
    }

    public function test_refuser_envoie_une_notification_a_l_employe(): void
    {
        Notification::fake();
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Pas dispo cette période, désolé',
            ]);

        Notification::assertSentTo($this->employe, DemandeAbsenceTraiteeNotification::class);
    }

    public function test_aucune_notification_si_validation_echoue(): void
    {
        Notification::fake();
        // Demande déjà refusée → la validation est rejetée
        $demande = $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande));

        Notification::assertNothingSent();
    }

    public function test_aucune_notification_si_refus_sans_justification(): void
    {
        Notification::fake();
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), ['commentaire' => '']);

        Notification::assertNothingSent();
    }

    // ========================================================================
    // Contenu de la notification
    // ========================================================================

    public function test_notification_porte_le_statut_validee(): void
    {
        Notification::fake();
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande));

        Notification::assertSentTo(
            $this->employe,
            DemandeAbsenceTraiteeNotification::class,
            function ($notification) use ($demande) {
                return $notification->demande->id === $demande->id
                    && $notification->demande->statut === DemandeAbsence::STATUT_VALIDEE;
            }
        );
    }

    public function test_notification_porte_le_statut_refusee(): void
    {
        Notification::fake();
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Période fermée',
            ]);

        Notification::assertSentTo(
            $this->employe,
            DemandeAbsenceTraiteeNotification::class,
            function ($notification) use ($demande) {
                return $notification->demande->statut === DemandeAbsence::STATUT_REFUSEE;
            }
        );
    }

    // ========================================================================
    // Contenu du mail
    // ========================================================================

    public function test_mail_a_le_bon_sujet_pour_validation(): void
    {
        $demande = $this->makeDemande([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
            'gestionnaire_id' => $this->gestionnaire->id,
        ]);
        $demande->load('gestionnaire');

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);

        $this->assertStringContainsString('acceptée', $mail->subject);
    }

    public function test_mail_a_le_bon_sujet_pour_refus(): void
    {
        $demande = $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);

        $this->assertStringContainsString('refusée', $mail->subject);
    }

    public function test_mail_contient_le_nom_du_gestionnaire(): void
    {
        $demande = $this->makeDemande([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
            'gestionnaire_id' => $this->gestionnaire->id,
        ]);
        $demande->load('gestionnaire');

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);
        $rendered = json_encode($mail->toArray(), JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('Marie Diop', $rendered);
    }

    public function test_mail_contient_les_dates_de_la_periode(): void
    {
        $demande = $this->makeDemande([
            'statut'     => DemandeAbsence::STATUT_VALIDEE,
            'date_debut' => '2026-07-10',
            'date_fin'   => '2026-07-20',
        ]);

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);
        $rendered = json_encode($mail->toArray(), JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('10/07/2026', $rendered);
        $this->assertStringContainsString('20/07/2026', $rendered);
    }

    public function test_mail_inclut_le_commentaire_si_present(): void
    {
        $demande = $this->makeDemande([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
            'commentaire_gestionnaire' => 'Excellent travail, profitez bien !',
        ]);

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);
        $rendered = json_encode($mail->toArray(), JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('Excellent travail', $rendered);
    }

    public function test_mail_n_inclut_pas_de_section_commentaire_si_absent(): void
    {
        $demande = $this->makeDemande([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
            'commentaire_gestionnaire' => null,
        ]);

        $mail = (new DemandeAbsenceTraiteeNotification($demande))->toMail($this->employe);
        $rendered = json_encode($mail->toArray(), JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString('Commentaire :', $rendered);
    }

    // ========================================================================
    // Payload base de données (cloche dans l'app)
    // ========================================================================

    public function test_payload_database_contient_les_infos_clefs(): void
    {
        $demande = $this->makeDemande([
            'statut' => DemandeAbsence::STATUT_VALIDEE,
            'gestionnaire_id' => $this->gestionnaire->id,
            'commentaire_gestionnaire' => 'OK pour moi',
        ]);
        $demande->load('gestionnaire');

        $data = (new DemandeAbsenceTraiteeNotification($demande))->toDatabase($this->employe);

        $this->assertEquals('demande_traitee', $data['type']);
        $this->assertEquals(DemandeAbsence::STATUT_VALIDEE, $data['statut']);
        $this->assertEquals($demande->id, $data['demande_id']);
        $this->assertEquals('Marie Diop', $data['gestionnaire_name']);
        $this->assertEquals('OK pour moi', $data['commentaire']);
        $this->assertStringContainsString('acceptée', $data['titre']);
    }

    public function test_payload_database_a_le_bon_titre_pour_refus(): void
    {
        $demande = $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $data = (new DemandeAbsenceTraiteeNotification($demande))->toDatabase($this->employe);

        $this->assertStringContainsString('refusée', $data['titre']);
    }

    // ========================================================================
    // Intégration : la notif arrive bien en base après valider/refuser
    // ========================================================================

    public function test_notification_persistee_en_base_apres_validation(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande), [
                'commentaire' => 'OK',
            ]);

        $this->assertEquals(1, $this->employe->notifications()->count());
        $notif = $this->employe->notifications()->first();
        $this->assertEquals(DemandeAbsenceTraiteeNotification::class, $notif->type);
        $this->assertEquals('validee', $notif->data['statut']);
    }

    public function test_notification_persistee_en_base_apres_refus(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Période fermée',
            ]);

        $this->assertEquals(1, $this->employe->notifications()->count());
        $notif = $this->employe->notifications()->first();
        $this->assertEquals('refusee', $notif->data['statut']);
        $this->assertEquals('Période fermée', $notif->data['commentaire']);
    }
}
