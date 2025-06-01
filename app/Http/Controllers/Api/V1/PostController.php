<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        $posts = Post::where('users_id', $user_id)->get();
        return response()->json($posts);
    }

    public function getPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 404);
        }

        $postId = $request->input('post_id');
        $post   = Post::where('id', $postId)->first() ?? abort(404);
        return response()->json($post);
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

            // Detectar menciones en el contenido y notificar a cada usuario mencionado
            $this->detectAndNotifyMentions(
                Auth::user(),
                'post',
                $post->id,
                $post->content ?? ''
            );

            return response()->json([
                'message' => 'Post creado correctamente',
                'post'    => $post
            ], 201);

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
        } else {
            return response()->json(['message' => 'No tienes permiso para eliminar este post.'], 403);
        }
    }

    /**
     * Extrae todas las “@username” de $text, busca usuarios y notifica a cada uno.
     */
    protected function detectAndNotifyMentions($mentioner, string $contextType, int $contextId, string $text)
    {
        // Regex para buscar @username
        preg_match_all('/@([a-zA-Z0-9_]+)/', $text, $matches);

        if (! empty($matches[1])) {
            $uniqueUsernames = array_unique($matches[1]);

            foreach ($uniqueUsernames as $username) {
                $user = User::where('username', $username)->first();

                if ($user && $user->id !== $mentioner->id) {
                    // Tomar un fragmento breve donde aparece la mención
                    $pos = mb_stripos($text, "@{$username}");
                    if ($pos !== false) {
                        $start   = max(0, $pos - 10);
                        $excerpt = mb_substr($text, $start, 30) . '…';
                    } else {
                        $excerpt = "@{$username}";
                    }

                    // Disparar la notificación en BD
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
