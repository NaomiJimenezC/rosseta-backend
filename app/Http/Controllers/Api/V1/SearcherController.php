<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class SearcherController extends Controller
{
    /**
     * Busca posts y usuarios según la query y devuelve ambos conjuntos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        $posts = Post::where('content', 'like', "%{$query}%")
            ->orWhere('caption', 'like', "%{$query}%")
            ->get();

        $users = User::where('username', 'like', "%{$query}%")
            ->get();

        $usersFormatted = UserResource::collection($users);

        return response()->json([
            'posts' => $posts,
            'users' => $usersFormatted,
        ]);
    }
}
