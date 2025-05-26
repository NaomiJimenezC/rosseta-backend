<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Enviar un mensaje dentro de una conversación
     */
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

        $conversation->update(['last_message_at' => now()]);

        broadcast(new DirectMessageSent($message));

        return response()->json($message);
    }
}