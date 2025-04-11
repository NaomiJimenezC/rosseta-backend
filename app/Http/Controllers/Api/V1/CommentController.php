<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\NewComment;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $request->input('post_id');
        $comments = Comment::where('post_id', $postId)->with('user')->latest()->get(); // Eager load user for comment author info

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para comentar.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'content' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'post_id' => $request->input('post_id'),
                'content' => $request->input('content'),
            ]);
            $comment->load('user');

            // Obtener el post al que pertenece el comentario
            $post = Post::findOrFail($request->input('post_id'));

            // Obtener el ID del creador del post
            $postCreatorId = $post->users_id;

            // Obtener el ID del usuario que creó el comentario
            $commenterId = Auth::id();

            // Verificar que el creador del post no sea el mismo que el que comenta
            if ($postCreatorId !== $commenterId) {
                broadcast(new NewComment($comment));
            }

            return response()->json(['message' => 'Comentario creado correctamente.', 'comment' => $comment], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el comentario.', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para eliminar un comentario.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $commentId = $request->input('comment_id');
        $comment = Comment::findOrFail($commentId);

        // Check if the authenticated user is the owner of the comment
        if (Auth::id() === $comment->user_id) {
            try {
                $comment->delete();
                return response()->json(['message' => 'Comentario eliminado correctamente.'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Error al eliminar el comentario.', 'error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'No tienes permiso para eliminar este comentario.'], 403);
        }
    }
}