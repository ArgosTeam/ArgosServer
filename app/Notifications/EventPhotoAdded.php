<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Storage;
use App\Models\Event;
use App\Models\Photo;

class EventPhotoAdded extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Event $event, Photo $photo, $via)
    {
        $this->event = $event;
        $this->photo = $photo;
        $this->via = $via;

        
        // Get signed url from s3
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";
            
        $command = $client->getCommand('GetObject', [
            'Bucket' => env('S3_BUCKET'),
            'Key'    => $photo->path,
        ]);
        $request = $client->createPresignedRequest($command);

        $this->path = '' . $request->getUri() . '';
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
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'photo_id' => $this->photo->id,
            'from_user_id' => $this->user->id,
            'from_user_name' => $this->user->firstName . ' ' . $this->user->lastName
        ];
    }

    public function toSlack($notifiable) {
        $url = $this->path;
        return (new SlackMessage)
            ->content($notifiable->firstName . ' ' . $notifiable->lastName
                      . ' ' . $notifiable->phone
                      . ' added a picture to event : '
                      . $this->event->name)
            ->attachment(function ($attachment) use($url) {
                $attachment->title('Link to Picture', $url);
            });
    }
}
