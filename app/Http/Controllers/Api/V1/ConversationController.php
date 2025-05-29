<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class ConversationController extends Controller
{
    /**
     * Listar hilos del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $conversations = $user->conversations()
                ->with(['users:id,username', 'messages' => function($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }])
                ->orderByDesc('last_message_at')
                ->get();

            return response()->json(['data' => $conversations]);
        } catch (Throwable $e) {
            //Log::error("Error listing conversations: {$e->getMessage()}");
            return response()->json([
                'message' => 'No se pudieron cargar las conversaciones.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un hilo existente y marcar como leído
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authorize('view', $conversation);

            // Marcar como leído: actualiza last_read_at en pivot
            $user->conversations()
                ->updateExistingPivot($conversation->id, [
                    'last_read_at' => now(),
                ]);

            // Cargar participantes
            $conversation->load('users:id,username');

            // Obtener mensajes paginados separado para evitar eager-load paginate
            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->paginate(50);

            return response()->json([
                'data'     => $conversation,
                'messages' => $messages,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver esta conversación.',
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Conversación no encontrada.',
            ], 404);
        } catch (Throwable $e) {
            //Log::error("Error showing conversation: {$e->getMessage()}");
            return response()->json([
                'message' => 'No se pudo cargar la conversación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener o crear conversación 1-a-1 con otro usuario
     */
    public function store(Request $request, User $other): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->id === $other->id) {
                return response()->json([
                    'message' => 'No puedes iniciar un chat contigo mismo.',
                ], 400);
            }

            $conversation = Conversation::whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->whereHas('users', function($q) use ($other) {
                    $q->where('user_id', $other->id);
                })
                ->first();

            if (! $conversation) {
                $conversation = Conversation::create();
                $conversation->users()->attach([
                    $user->id  => ['joined_at' => now()],
                    $other->id => ['joined_at' => now()],
                ]);
            }

            $conversation->load('users:id,name');

            return response()->json(['data' => $conversation], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        } catch (QueryException $e) {
            //\Log::error("DB error creating conversation: {$e->getMessage()}");
            return response()->json([
                'message' => 'Error en la base de datos al iniciar conversación.',
                'error'   => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            //Log::error("Error creating conversation: {$e->getMessage()}");
            return response()->json([
                'message' => 'No se pudo iniciar la conversación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Contar hilos con mensajes pendientes (no leídos) — optimizado para PostgreSQL
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $count = Conversation::query()
                ->join('conversation_user as cu', function ($join) use ($userId) {
                    $join->on('cu.conversation_id', 'conversations.id')
                        ->where('cu.user_id', $userId);
                })
                ->join('messages as m', 'm.conversation_id', 'conversations.id')
                ->where(function ($q) {
                    $q->whereColumn('m.created_at', '>', 'cu.last_read_at')
                        ->orWhereNull('cu.last_read_at');
                })
                ->distinct('conversations.id')
                ->count('conversations.id');

            return response()->json(['unread_chats' => $count]);
        } catch (Throwable $e) {
            //Log::error("Error counting unread chats: {$e->getMessage()}");
            return response()->json([
                'message' => 'Error al contar chats pendientes.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
