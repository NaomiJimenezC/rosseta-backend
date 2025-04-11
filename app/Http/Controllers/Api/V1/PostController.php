<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $userId = $request->input('user_id');

        // Assuming you have a Post model with a user_id foreign key
        $posts = Post::where('users_id', $userId)->get();

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
        $post = Post::where('id', $postId)->first() ?? abort(404);

        return response()->json($post);
    }
    public function store(Request $request){
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para crear un post.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'string|nullable',
            'image_url' => 'string|required',
            'caption' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $post = Post::create([
                'users_id' => $userId = $request->user()->id,
                'image_url' => $request->input('image_url'),
                'content' => $request->input('content'),
                'caption' => $request->input('caption'),
            ]);

            $post->save();
            return response()->json(['message' => 'Post creado correctamente', 'post' => $post], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el post', 'error' => $e->getMessage()], 422);
        }
    }

    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id', // Asegúrate de que el post_id existe
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $postId = $request->input('post_id');
        $post = Post::findOrFail($postId); // Obtener el post o lanzar un 404 si no existe

        // Verificar si el usuario autenticado es el propietario del post
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
}