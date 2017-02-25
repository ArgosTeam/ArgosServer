<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use App\Models\Event;
use App\Models\User;

class EventInvite extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Event $event, $via)
    {
        $this->user = $user;
        $this->event = $event;
        $this->via = $via;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [$this->via];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', 'https://laravel.com')
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'from_user_id' => $this->user->id,
            'from_user_name' => $this->user->firstName,
            'event_id' => $this->event->id,
            'event_name' => $this->event->name
        ];
    }

    public function toSlack($notifiable) {
        return (new SlackMessage)
            ->success()
            ->content($notifiable->firstName . ' ' $notifable->lastName
                      . ' ' . $notifiable->phone
                      . ' invited '
                      . $this->user->firstName . ' ' $this->user->lastName
                      . ' ' . $this->user->phone . ' to join event : '
                      . $this->event->name);
    }
}
