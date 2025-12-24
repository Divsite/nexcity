<?php

namespace App\Notifications\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Example on how to trigger notification
 *
 * Notification::send(auth()->user(), new NotifyUser([
 * 'channel'   => define either ['mail'] or ['database'] o ['mail','database'],
 * 'subject'   => subject for your email if notify by email,
 * 'message'   => message for the notification,
 * 'redirect'  => redirect url for the action button for mail channel,
 * 'form_id'   => provide form id if related to form
 * ]));
 */
class NotifyUser extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return $this->data['channel'] ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->data['subject'])
            ->line($this->data['data'])
            ->action($this->data['action_title'], url($this->data['redirect']))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'form_id' => $this->data['form_id'] ?? null,
            'message' => $this->data['data']
        ];
    }
}
