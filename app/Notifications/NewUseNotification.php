<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUseNotification extends Notification
{
    use Queueable;


    public $user_id;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($user_id,$message)
    {
        $this->user_id = $user_id;
        $this->message = $message;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

  
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id'=>$this->user_id,
            'message'=>$this->message
        ];
    }
}
