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
     * Display a listing of posts with like information for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para ver los likes.'], 401);
        }

        $posts = Like::all();

        return response()->json($posts);
    }

    /**
     * Store a newly created like in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
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

        // Check if the user has already liked the post
        $existingLike = Like::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($existingLike) {
            return response()->json(['message' => 'Ya has dado me gusta a esta publicación.'], 409); // 409 Conflict
        }

        try {
            $like = Like::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);

            // Broadcast the PostLiked event
            $post = Post::findOrFail($postId);
            if ($post->users_id !== $userId) {
                broadcast(new PostLiked($post, Auth::user()));
            }

            return response()->json(['message' => 'Me gusta añadido correctamente.'], 201);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error al dar me gusta (base de datos).', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al dar me gusta.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified like from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        if (!Auth::check()) {
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

        $like = Like::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if (!$like) {
            return response()->json(['message' => 'No has dado me gusta a esta publicación.'], 404);
        }

        try {
            $like->delete();
            return response()->json(['message' => 'Me gusta eliminado correctamente.'], 200);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error al quitar el me gusta (base de datos).', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al quitar el me gusta.', 'error' => $e->getMessage()], 500);
        }
    }
}