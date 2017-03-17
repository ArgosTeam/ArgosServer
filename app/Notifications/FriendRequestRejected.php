<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use App\Models\User;

class FriendRequestRejected extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, User $friend, $via)
    {
        $this->user = $user;
        $this->friend = $friend;
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
            'user_name' => $this->friend->firstname . ' ' . $this->friend->lastname,
            'user_id' => $this->friend->id,
            'status' => 'refused'
        ];
    }

    public function toSlack($notifiable) {
        $user_id = $this->user->id;
        $friend_id = $this->friend->id;
        return (new SlackMessage)
            ->success()
            ->content($this->user->firstname . ' ' . $this->user->lastname
                      . ' ' . $this->user->phone
                      . ' refused friend request from '
                      . $this->friend->firstname . ' ' . $this->friend->lastname . ' '
                      . $this->friend->phone);
    }
}
