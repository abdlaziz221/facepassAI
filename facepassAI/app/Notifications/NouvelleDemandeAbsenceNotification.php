<?php

namespace App\Notifications;

use App\Models\DemandeAbsence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée aux gestionnaires quand un employé crée une demande
 * d'absence (Sprint 4 Horaires carte 9, US-050).
 *
 * Canaux :
 *   - database : stockée en BD pour la cloche 🔔 dans le header
 *   - mail     : email avec template Laravel par défaut
 */
class NouvelleDemandeAbsenceNotification extends Notification
{
    use Queueable;

    public function __construct(public DemandeAbsence $demande) {}

    /**
     * Canaux utilisés.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Représentation pour la base de données (canal 'database').
     * Sera disponible via $user->notifications dans le header.
     */
    public function toDatabase(object $notifiable): array
    {
        $this->demande->loadMissing('employe.user');

        return [
            'demande_id'  => $this->demande->id,
            'employe_id'  => $this->demande->employe_id,
            'employe_nom' => $this->demande->employe->user->name ?? 'Inconnu',
            'date_debut'  => $this->demande->date_debut->toDateString(),
            'date_fin'    => $this->demande->date_fin->toDateString(),
            'motif'       => $this->demande->motif,
            'message'     => sprintf(
                "%s a déposé une demande d'absence du %s au %s.",
                $this->demande->employe->user->name ?? 'Un employé',
                $this->demande->date_debut->format('d/m/Y'),
                $this->demande->date_fin->format('d/m/Y')
            ),
        ];
    }

    /**
     * Template email (canal 'mail').
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->demande->loadMissing('employe.user');

        $nomEmploye = $this->demande->employe->user->name ?? 'Un employé';
        $debut      = $this->demande->date_debut->format('d/m/Y');
        $fin        = $this->demande->date_fin->format('d/m/Y');

        return (new MailMessage)
            ->subject("Nouvelle demande d'absence — {$nomEmploye}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle demande d'absence vient d'être déposée par **{$nomEmploye}**.")
            ->line("**Période demandée :** du {$debut} au {$fin}")
            ->line("**Motif :** {$this->demande->motif}")
            ->action('Voir les demandes', url('/dashboard'))
            ->line("Cette demande est en attente de votre validation. Vous pouvez l'accepter ou la refuser depuis votre tableau de bord.")
            ->salutation('FacePass AI — Notification automatique');
    }

    /**
     * Pour la sérialisation array (utilisé par toBroadcast ou tests).
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
