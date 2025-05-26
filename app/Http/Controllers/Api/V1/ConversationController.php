<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    /**
     * Listar hilos del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['users:id,name', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json(['data' => $conversations]);
    }

    /**
     * Mostrar un hilo existente y marcar como leído, o crear uno nuevo
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        $this->authorize('view', $conversation);

        // Marcar como leído: actualizar last_read_at en pivot
        $user->conversations()
            ->updateExistingPivot($conversation->id, ['last_read_at' => now()]);

        // Cargar mensajes paginados y participantes
        $conversation->load(['users:id,name', 'messages' => fn($q) => $q->orderBy('created_at')->paginate(50)]);

        return response()->json(['data' => $conversation]);
    }

    /**
     * Obtener o crear conversación 1-a-1 con otro usuario
     */
    public function store(Request $request, User $other): JsonResponse
    {
        $user = $request->user();

        abort_if($user->id === $other->id, 400, 'No puedes iniciar chat contigo mismo.');

        // Buscar o crear conversación entre ambos
        $conversation = Conversation::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->whereHas('users', fn($q) => $q->where('user_id', $other->id))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create();
            $conversation->users()->attach([
                $user->id => ['joined_at' => now()],
                $other->id => ['joined_at' => now()],
            ]);
        }

        return response()->json(['data' => $conversation->load('users:id,name')], 201);
    }

    /**
     * Contar hilos con mensajes pendientes (no leídos)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = Conversation::whereHas('messages', function ($q) use ($userId) {
            $q->where('created_at', '>', function ($query) use ($userId) {
                $query->select('last_read_at')
                    ->from('conversation_user')
                    ->whereColumn('conversation_user.conversation_id', 'messages.conversation_id')
                    ->where('conversation_user.user_id', $userId)
                    ->limit(1);
            })
                ->orWhereRaw("(
                SELECT last_read_at
                FROM conversation_user
                WHERE conversation_id = messages.conversation_id
                  AND user_id = {$userId}
            ) IS NULL");
        })
            ->distinct()
            ->count('id');

        return response()->json(['unread_chats' => $count]);
    }
}