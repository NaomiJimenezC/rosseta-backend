<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aquí defines los callbacks de autorización para cada canal.
|
*/

// (Opcional) Canal genérico por usuario, para usos variados
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 1) Canal privado de notificaciones por usuario
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// 2) Canal de presencia para chat 1-a-1
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()
        ->where('conversations.id', $conversationId)
        ->exists()
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
