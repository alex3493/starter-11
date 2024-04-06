<?php

use Illuminate\Support\Facades\Broadcast;

// We are calling channel auth from both Laravel UI and mobile app.
Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.updates', function () {
    // All signed-in users can subscribe to chatroom updates.
    return true;
});

Broadcast::channel('chat.updates.{id}', function () {
    // All signed-in users can subscribe to given chatroom updates.
    return true;
});

// Presence channel.
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    if ($user->hasJoinedRoom($roomId)) {
        return $user->toArray();
    }
});

