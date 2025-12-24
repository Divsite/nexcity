<?php

namespace App\Notifications\Users;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserChangedEmail extends VerifyEmailNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Get to verify email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Changed Email Address')
            ->line('You has change your email.')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $url);
    }
}
