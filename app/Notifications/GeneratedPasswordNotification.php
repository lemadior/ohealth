<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneratedPasswordNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $password  Generated password emailed to a freshly provisioned user.
     */
    public function __construct(private readonly string $password)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  object  $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('auth.login.generated_password.subject'))
            ->greeting(__('auth.login.generated_password.greeting'))
            ->line(__('auth.login.generated_password.intro_line'))
            ->line(__('auth.login.generated_password.password_line', ['password' => $this->password]))
            ->line(__('auth.login.generated_password.security_line'));
    }
}
