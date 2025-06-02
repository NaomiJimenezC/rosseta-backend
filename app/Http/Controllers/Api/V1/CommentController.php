<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Resources\UserResource;
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

        // Traemos los comentarios con su usuario (solo para tener los datos en memoria).
        $comments = Comment::where('post_id', $postId)
            ->with('user')
            ->latest()
            ->get();

        // Mapeamos cada comentario para que el usuario vaya dentro de UserResource
        $result = $comments->map(function($c) {
            return [
                'id'         => $c->id,
                'post_id'    => $c->post_id,
                'user_id'    => $c->user_id,
                'content'    => $c->content,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
                'user'       => new UserResource($c->user),
            ];
        });

        return response()->json($result);
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

            $post = Post::findOrFail($request->input('post_id'));
            $postCreatorId = $post->users_id;
            $commenterId   = Auth::id();
            if ($postCreatorId !== $commenterId) {
                broadcast(new NewComment($comment));
            }

            $responseData = [
                'id'         => $comment->id,
                'post_id'    => $comment->post_id,
                'user_id'    => $comment->user_id,
                'content'    => $comment->content,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user'       => new UserResource($comment->user),
            ];

            return response()->json(['message' => 'Comentario creado correctamente.', 'comment' => $responseData], 201);

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