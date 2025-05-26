<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Listar hilos del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = $user->conversations()
        ->with(['users', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
        ->get();

        return response()->json($conversations);
    }

    /**
     * Obtener o crear conversación 1-a-1 con otro usuario
     */
    public function showOrCreate(Request $request, $otherUserId)
    {
        $user  = $request->user();
        $other = User::findOrFail($otherUserId);

        // Buscar conversación existente
        $conversation = Conversation::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->whereHas('users', fn($q) => $q->where('user_id', $other->id))
            ->first();

        if (!$conversation) {
        $conversation = Conversation::create();
            $conversation->users()->attach([
            $user->id  => ['joined_at' => now()],
                $other->id => ['joined_at' => now()]
            ]);
        }

        return response()->json($conversation->load(['users', 'messages']));
    }
}