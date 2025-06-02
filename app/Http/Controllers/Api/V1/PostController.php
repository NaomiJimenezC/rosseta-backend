<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class PostController extends Controller
{
    public function index($user_id)
    {
        if (! is_numeric($user_id)) {
            return response()->json(['error' => 'Invalid user id'], 400);
        }

        $posts = Post::with('user')
            ->where('users_id', $user_id)
            ->get();

        return PostResource::collection($posts);
    }

    public function getPost($post_id)
    {
        if (! is_numeric($post_id)) {
            return response()->json(['error' => 'Invalid post id'], 400);
        }

        $post = Post::with('user')
            ->where('id', $post_id)
            ->firstOrFail();

        return new PostResource($post);
    }

    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para crear un post.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'content'   => 'nullable|string',
            'image_url' => 'required|string',
            'caption'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $post = Post::create([
                'users_id'  => $request->user()->id,
                'image_url' => $request->input('image_url'),
                'content'   => $request->input('content'),
                'caption'   => $request->input('caption'),
            ]);

            $post->load('user');

            $this->detectAndNotifyMentions(
                Auth::user(),
                'post',
                $post->id,
                $post->content ?? ''
            );

            return new PostResource($post);

        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Error en la base de datos al crear el post.',
                'error'   => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el post',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $request->input('post_id');
        $post   = Post::findOrFail($postId);

        if (Auth::check() && Auth::user()->id === $post->users_id) {
            try {
                $post->delete();
                return response()->json(['message' => 'Post eliminado correctamente'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Error al eliminar el post'], 500);
            }
        }

        return response()->json(['message' => 'No tienes permiso para eliminar este post.'], 403);
    }

    protected function detectAndNotifyMentions($mentioner, string $contextType, int $contextId, string $text)
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $text, $matches);

        if (! empty($matches[1])) {
            $uniqueUsernames = array_unique($matches[1]);

            foreach ($uniqueUsernames as $username) {
                $user = User::where('username', $username)->first();

                if ($user && $user->id !== $mentioner->id) {
                    $pos = mb_stripos($text, "@{$username}");
                    if ($pos !== false) {
                        $start   = max(0, $pos - 10);
                        $excerpt = mb_substr($text, $start, 30) . '…';
                    } else {
                        $excerpt = "@{$username}";
                    }

                    $user->notify(new MentionNotification(
                        $mentioner,
                        $contextType,
                        $contextId,
                        $excerpt
                    ));
                }
            }
        }
    }
}
