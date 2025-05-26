<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Enviar un mensaje dentro de una conversación
     */
    public function store(Request $request, $conversationId)
    {
        $request->validate(['content' => 'required|string']);

        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $message = Message::create([
        'conversation_id' => $conversation->id,
            'sender_id'       => $request->user()->id,
            'content'         => $request->input('content'),
        ]);

        // Actualizar marca de tiempo del último mensaje
        $conversation->update(['last_message_at' => now()]);

        // Emitir evento de broadcast
        event(new \App\Events\MessageSent($message));

        return response()->json($message);
    }
}