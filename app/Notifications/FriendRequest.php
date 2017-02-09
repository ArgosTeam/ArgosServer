<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use App\Models\User;

class FriendRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, User $friend)
    {
        $this->user = $user;
        $this->friend = $friend;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
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
            //
        ];
    }

    public function toSlack($notifiable) {
        $user_id = $this->user->id;
        $friend_id = $this->friend->id;
        return (new SlackMessage)
            ->success()
            ->content($this->user->firstName . ' ' . $this->user->firstName
                      . ' sent a friend request to '
                      . $this->friend->firstName . ' ' . $this->friend->firstName)
            ->attachment(function ($attachment) use ($user_id, $friend_id) {
                    $attachment->title('More info')
                               ->fields([
                                    'user_id' => $user_id,
                                    'friend_id' => $friend_id
                                ]);
            });
    }
}
