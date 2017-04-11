<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Storage;
use App\Models\Group;
use App\Models\Photo;

class GroupPhotoAdded extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Group $group, Photo $photo, $via)
    {
        $this->group = $group;
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
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'photo_id' => $this->photo->id,
            'user_id' => $this->user->id,
        ];
    }

    public function toSlack($notifiable) {
        $url = $this->path;
        return (new SlackMessage)
            ->content($notifiable->nickname
                      . ' ' . $notifiable->phone
                      . ' added a picture to group : '
                      . $this->group->name)
            ->attachment(function ($attachment) use($url) {
                $attachment->title('Link to Picture', $url);
            });
    }
}
