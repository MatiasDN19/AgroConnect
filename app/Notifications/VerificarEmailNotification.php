<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerificarEmailNotification extends VerifyEmail
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verificá tu correo electrónico - AgroConnect')
            ->greeting('¡Hola, '.$notifiable->name.'!')
            ->line('Gracias por unirte a AgroConnect. Para comenzar a publicar, solicitar compras, reseñar y denunciar, por favor verificá tu dirección de correo electrónico.')
            ->action('Verificar mi correo', $verificationUrl)
            ->line('Si no creaste una cuenta en AgroConnect, podés ignorar este mensaje.');
    }
}
