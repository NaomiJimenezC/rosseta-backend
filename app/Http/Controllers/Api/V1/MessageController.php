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
    public function index(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);
            $this->authorize('view', $conversation);

            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->paginate(50);

            return response()->json($messages, 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Conversación no encontrada.'], 404);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'No tienes permiso para ver los mensajes de esta conversación.'], 403);

        } catch (Exception $e) {
            Log::error('Error al listar mensajes', [
                'conversation_id' => $conversationId,
                'user_id'         => optional($request->user())->id,
                'exception'       => $e->getMessage(),
                'trace'           => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ha ocurrido un error al cargar los mensajes.'], 500);
        }
    }

    public function store(Request $request, $conversationId)
    {
        try {
            $request->validate(['content' => 'required|string']);

            $conversation = Conversation::findOrFail($conversationId);
            $this->authorize('view', $conversation);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $request->user()->id,
                'content'         => $request->input('content'),
            ]);

            $conversation->update(['last_message_at' => now()]);

            Log::info("Emitiendo evento DirectMessageSent", [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $message->sender_id
            ]);

            broadcast(new DirectMessageSent($message))->toOthers();

            return response()->json($message, 201);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Conversación no encontrada.'], 404);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'No tienes permiso para acceder a esta conversación.'], 403);

        } catch (QueryException $e) {
            Log::error('Error SQL al enviar mensaje', [
                'conversation_id' => $conversationId,
                'user_id'         => optional($request->user())->id,
                'content'         => $request->input('content'),
                'sql_error'       => $e->getMessage()
            ]);
            return response()->json(['error' => 'Error en la base de datos al guardar el mensaje.'], 500);

        } catch (Exception $e) {
            Log::error('Error inesperado al enviar mensaje', [
                'conversation_id' => $conversationId,
                'user_id'         => optional($request->user())->id,
                'content'         => $request->input('content'),
                'exception'       => $e->getMessage(),
                'trace'           => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Ha ocurrido un error inesperado.'], 500);
        }
    }
}
