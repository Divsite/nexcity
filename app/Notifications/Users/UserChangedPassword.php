<?php

namespace App\Notifications\Users;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserChangedPassword extends VerifyEmailNotification implements ShouldQueue
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
            ->subject('Changed Password')
            ->line('You has change your password. Login using your new password.');
    }
}
