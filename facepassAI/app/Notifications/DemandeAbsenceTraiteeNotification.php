<?php

namespace App\Notifications;

use App\Models\DemandeAbsence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée à l'employé quand sa demande d'absence
 * est validée ou refusée par le gestionnaire (Sprint 4 carte 11, US-053).
 *
 * Canaux : database (cloche dans l'app) + mail.
 */
class DemandeAbsenceTraiteeNotification extends Notification
{
    use Queueable;

    public function __construct(public DemandeAbsence $demande)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estValidee   = $this->demande->statut === DemandeAbsence::STATUT_VALIDEE;
        $debut        = $this->demande->date_debut->format('d/m/Y');
        $fin          = $this->demande->date_fin->format('d/m/Y');
        $gestionnaire = $this->demande->gestionnaire?->name ?? 'votre gestionnaire';

        $mail = (new MailMessage)
            ->subject($estValidee
                ? "Votre demande d'absence a été acceptée"
                : "Votre demande d'absence a été refusée")
            ->greeting("Bonjour {$notifiable->name},");

        if ($estValidee) {
            $mail->line("Bonne nouvelle ! Votre demande d'absence du {$debut} au {$fin} a été acceptée par {$gestionnaire}.");
        } else {
            $mail->line("Votre demande d'absence du {$debut} au {$fin} a été refusée par {$gestionnaire}.");
        }

        if ($this->demande->commentaire_gestionnaire) {
            $mail->line('Commentaire : ' . $this->demande->commentaire_gestionnaire);
        }

        $mail->action('Voir mes demandes', route('dashboard'));

        if ($estValidee) {
            $mail->line('Bon repos !');
        } else {
            $mail->line("Vous pouvez déposer une nouvelle demande pour d'autres dates si besoin.");
        }

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        $estValidee = $this->demande->statut === DemandeAbsence::STATUT_VALIDEE;

        return [
            'type'              => 'demande_traitee',
            'statut'            => $this->demande->statut,
            'demande_id'        => $this->demande->id,
            'date_debut'        => $this->demande->date_debut->toDateString(),
            'date_fin'          => $this->demande->date_fin->toDateString(),
            'gestionnaire_id'   => $this->demande->gestionnaire_id,
            'gestionnaire_name' => $this->demande->gestionnaire?->name,
            'commentaire'       => $this->demande->commentaire_gestionnaire,
            'titre'             => $estValidee
                ? "Votre demande d'absence a été acceptée"
                : "Votre demande d'absence a été refusée",
            'url'               => route('dashboard'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
