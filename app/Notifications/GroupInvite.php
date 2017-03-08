<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use App\Models\Group;
use App\Models\User;

class GroupInvite extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Group $group, $via)
    {
        $this->user = $user;
        $this->group = $group;
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
            'group_id' => $this->group->id,
            'group_name' => $this->group->name
        ];
    }

    public function toSlack($notifiable) {
        return (new SlackMessage)
            ->success()
            ->content($notifiable->firstName . ' ' . $notifiable->lastName
                      . ' ' . $notifiable->phone
                      . ' invited '
                      . $this->user->firstName . ' ' . $this->user->lastName
                      . ' ' . $this->user->phone . ' to join group : '
                      . $this->group->name);
    }
}
