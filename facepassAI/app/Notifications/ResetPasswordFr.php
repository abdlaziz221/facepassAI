<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification de réinitialisation de mot de passe en français.
 *
 * Sprint 1, US-013. Étend la notification Breeze/Laravel et
 * traduit le contenu de l'email + l'adapte à FacePass AI.
 */
class ResetPasswordFr extends ResetPassword
{
    /**
     * Construire le contenu de l'email.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — FacePass AI')
            ->greeting('Bonjour '.($notifiable->name ?? '').',')
            ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte FacePass AI.')
            ->action('Réinitialiser mon mot de passe', $url)
            ->line("Ce lien expire dans {$minutes} minutes.")
            ->line("Si vous n'êtes pas à l'origine de cette demande, vous pouvez simplement ignorer cet email — votre mot de passe restera inchangé.")
            ->salutation('— L\'équipe FacePass AI');
    }
}
