<?php

namespace App\Notifications;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifySubscription extends Notification implements ShouldQueue
{
    use Queueable;

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
     * Get the mail representation of the notification.
     */
    public function toMail(Subscriber $notifiable): MailMessage
    {
        $verifyUrl = URL::temporarySignedRoute(
            'subscribe.verify',
            now()->addHours(24),
            ['subscriber' => $notifiable->id]
        );

        $unsubscribeUrl = URL::signedRoute(
            'subscribe.unsubscribe',
            ['subscriber' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Verify Your Daily Digest Subscription')
            ->greeting('Welcome to StreetCode Daily Digest!')
            ->line('Thank you for subscribing to our daily newsletter. Please confirm your email address by clicking the button below.')
            ->action('Verify Email Address', $verifyUrl)
            ->line('This verification link will expire in 24 hours.')
            ->line('If you did not subscribe to our newsletter, you can safely ignore this email or click the link below to unsubscribe.')
            ->line('[Unsubscribe]('.$unsubscribeUrl.')');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
