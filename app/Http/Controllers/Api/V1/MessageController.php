<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Exception;

class MessageController extends Controller
{
    /**
     * Enviar un mensaje dentro de una conversación
     */
    public function store(Request $request, $conversationId)
    {
        try {
            $request->validate([
                'content' => 'required|string'
            ]);

            $conversation = Conversation::findOrFail($conversationId);

            $this->authorize('view', $conversation);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $request->user()->id,
                'content'         => $request->input('content'),
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new DirectMessageSent($message))->toOthers();

            return response()->json($message, 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Conversación no encontrada.'
            ], 404);

        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'No tienes permiso para acceder a esta conversación.'
            ], 403);

        } catch (QueryException $e) {
            Log::error('Error SQL al enviar mensaje', [
                'conversation_id' => $conversationId,
                'user_id'         => optional($request->user())->id,
                'message'         => $request->input('content'),
                'sql_error'       => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Error en la base de datos al guardar el mensaje.'
            ], 500);

        } catch (Exception $e) {
            Log::error('Error inesperado al enviar mensaje', [
                'conversation_id' => $conversationId,
                'user_id'         => optional($request->user())->id,
                'message'         => $request->input('content'),
                'exception'       => $e->getMessage(),
                'trace'           => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Ha ocurrido un error inesperado.'
            ], 500);
        }
    }
}
