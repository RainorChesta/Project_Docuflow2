<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Private channel authorization for notifications.
 * A user can only listen to their own notification channel.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
