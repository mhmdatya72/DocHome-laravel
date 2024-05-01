<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class MessageSent extends Notification
{
    use Queueable;

    /**
     * message sent constructor.
     */
    public function __construct(private array $data)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via()
    {
        return [OneSignalChannel::class];
    }

    public function toOneSignal()
    {
        $meesageData = $this->data['meesageData'];

        return OneSignalMessage::create()
            ->setSubject($meesageData['senderName'] . "send you a message")
            ->setBody($meesageData['message'])
            ->setData('data', $meesageData);
    }

    
}
