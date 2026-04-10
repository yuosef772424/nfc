<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        switch ($notification->channel) {
            case 'push':
                $this->sendPushNotification($notification);
                break;
            case 'email':
                $this->sendEmailNotification($notification);
                break;
            case 'sms':
                $this->sendSmsNotification($notification);
                break;
        }
    }

    protected function sendPushNotification(Notification $notification): void
    {
        // TODO: Implement FCM / APNS logic
        Log::info("Push notification sent to user {$notification->user_id}: {$notification->title}");
    }

    protected function sendEmailNotification(Notification $notification): void
    {
        // TODO: Implement Mail facade
        Log::info("Email notification sent to user {$notification->user_id}: {$notification->title}");
    }

    protected function sendSmsNotification(Notification $notification): void
    {
        // TODO: Implement SMS provider
        Log::info("SMS notification sent to user {$notification->user_id}: {$notification->message}");
    }
}