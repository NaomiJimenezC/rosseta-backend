<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\PostLiked;
use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LikeController extends Controller
{
    /**
     * Display a listing of likes for the authenticated user.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para ver los likes.'], 401);
        }

        $likes = Like::where('user_id', Auth::id())->get();
        return response()->json($likes);
    }

    /**
     * Store a newly created like in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para dar me gusta.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $request->input('post_id');
        $userId = Auth::id();

        // Evitar likes duplicados
        if (Like::where('post_id', $postId)->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'Ya has dado me gusta a esta publicación.'], 409);
        }

        try {
            // Crear el like
            $like = Like::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);

            // Disparar el evento correcto: primero el usuario, luego el post
            $post = Post::findOrFail($postId);
            if ($post->user_id !== $userId) {
                broadcast(new PostLiked(Auth::user(), $post));
            }

            return response()->json(['message' => 'Me gusta añadido correctamente.'], 201);

        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error al dar me gusta (base de datos).',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al dar me gusta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified like.
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para quitar el me gusta.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $request->input('post_id');
        $userId = Auth::id();

        $like = Like::where('post_id', $postId)->where('user_id', $userId)->first();
        if (! $like) {
            return response()->json(['message' => 'No has dado me gusta a esta publicación.'], 404);
        }

        try {
            $like->delete();
            return response()->json(['message' => 'Me gusta eliminado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error al quitar el me gusta (base de datos).',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al quitar el me gusta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show all likes for a post.
     */
    public function show(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $validator->validated()['post_id'];
        $likes  = Like::where('post_id', $postId)->get();

        return response()->json($likes);
    }

    /**
     * Check if the authenticated user has liked a post.
     */
    public function hasLiked(Request $request, $postId): \Illuminate\Http\JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['hasLiked' => false]);
        }

        $hasLiked = Like::where('user_id', Auth::id())
            ->where('post_id', $postId)
            ->exists();

        return response()->json(['hasLiked' => $hasLiked]);
    }
}
