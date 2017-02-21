<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Messages\SlackMessage;
use App\Models\Photo;
use App\Models\User;

class NewPublicPicture extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Photo $photo, $via)
    {
        // Get signed url from s3
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";
            
        $command = $client->getCommand('GetObject', [
            'Bucket' => env('S3_BUCKET'),
            'Key'    => "avatar-" . $photo->path,
        ]);
        $request = $client->createPresignedRequest($command, $expiry);
        $this->user = $user;
        $this->photo = $photo;
        $this->path = '' . $request->getUri() . '';
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
            'from_user_name' => $this->user->firstName . ' ' . $this->user->lastName,
            'path' => $this->path,
            'photo_id' => $this->photo->id
        ];
    }

    /**
     * Route for slack notification
     *
     * @param mixed $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable) {
        $url = $this->path;
        return (new SlackMessage)
            ->success()
            ->content($this->user->firstName . ' ' . $this->user->lastName . ' '
                      . $this->user->phone
                      . ' uploaded a public picture')
            ->attachment(function ($attachment) use ($url) {
                $attachment->footer_icon($url);
            });
    }
}
