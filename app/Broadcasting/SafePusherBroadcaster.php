<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;
use Pusher\ApiErrorException;
use Throwable;

class SafePusherBroadcaster extends PusherBroadcaster
{
    /**
     * Broadcast the given event safely without throwing uncaught exceptions
     * if the WebSocket/Reverb server is offline or unreachable.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            parent::broadcast($channels, $event, $payload);
        } catch (BroadcastException | ApiErrorException $e) {
            Log::warning('WebSocket broadcast failed (Reverb server may be offline or unreachable): ' . $e->getMessage(), [
                'event' => $event,
                'channels' => $channels,
            ]);
        } catch (Throwable $e) {
            Log::warning('Unexpected error during WebSocket broadcast: ' . $e->getMessage(), [
                'event' => $event,
                'channels' => $channels,
            ]);
        }
    }
}
