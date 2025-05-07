<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostFolloweeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $followingIds = $user->following()->pluck('followee_id');
        $followeeIds = $followingIds->push($user->id)->unique();
        $posts = Post::whereIn('users_id', $followeeIds)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($posts);
    }
}