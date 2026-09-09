<?php

namespace App\Channels;

use Illuminate\Notifications\Channels\BroadcastChannel as BaseBroadcastChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SafeBroadcastChannel extends BaseBroadcastChannel
{
    /**
     * Send the given notification safely.
     * Prevents WebSocket broadcast errors from failing the entire notification delivery or HTTP request.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (Throwable $e) {
            Log::warning('Broadcast notification delivery failed (WebSocket offline or unreachable): ' . $e->getMessage(), [
                'notification' => get_class($notification),
                'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            ]);

            return null;
        }
    }
}
