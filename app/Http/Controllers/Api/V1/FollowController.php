<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\UserFollowed;
use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FollowController extends Controller
{
    /**
     * Follow a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user The user to follow.
     * @return \Illuminate\Http\JsonResponse
     */
    public function follow(Request $request, User $user)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para seguir a otros usuarios.'], 401);
        }

        $followerId = Auth::id();
        $followeeId = $user->id;

        if ($followerId === $followeeId) {
            return response()->json(['message' => 'No puedes seguirte a ti mismo.'], 400);
        }

        $existingFollow = Follow::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->first();

        if ($existingFollow) {
            return response()->json(['message' => 'Ya estás siguiendo a este usuario.'], 409); // 409 Conflict
        }

        try {
            Follow::create([
                'follower_id' => $followerId,
                'followee_id' => $followeeId,
            ]);

            $follower = Auth::user();
            $followee = $user;
            if ($followerId !== $followeeId) {
                broadcast(new UserFollowed($follower, $followee));
            }

            return response()->json(['message' => 'Has comenzado a seguir a este usuario.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al seguir al usuario.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unfollow a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user The user to unfollow.
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfollow(Request $request, User $user)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Debes estar autenticado para dejar de seguir usuarios.'], 401);
        }

        $followerId = Auth::id();
        $followeeId = $user->id;

        $follow = Follow::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'No estás siguiendo a este usuario.'], 404);
        }

        try {
            $follow->delete();
            return response()->json(['message' => 'Has dejado de seguir a este usuario.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al dejar de seguir al usuario.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the followers of a specific user.
     *
     * @param  \App\Models\User  $user The user to get followers for.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get the followers of a specific user.
     *
     * @param  \App\Models\User  $user The user to get followers for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowers(User $user): JsonResponse
    {
        try {
            $followers = $user->followers()->paginate(10);
            return response()->json($followers);
        } catch (\Exception $e) {
            Log::error('Error fetching followers for user ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener los seguidores.'], 500);
        }
    }


    /**
     * Get the users that a specific user is following.
     *
     * @param  \App\Models\User  $user The user to get the following list for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFollowing(User $user): JsonResponse
    {
        try {
            $following = $user->following()->with('followee')->paginate(10); // Eager load followee details
            return response()->json($following);
        } catch (Exception $e) {
            Log::error('Error fetching following for user ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener los usuarios seguidos.'], 500);
        }
    }

    public function isFollowing(User $user): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json(['message' => 'No estás autenticado.'], 401);
            }
            if ($currentUser->id === $user->id) {
                return response()->json(['message' => 'No puedes seguirte a ti mismo.'], 400);
            }

            $isFollowing = $currentUser->isFollowing($user);

            return response()->json(['isFollowing' => $isFollowing], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error interno al procesar la solicitud.', 'error' => $e->getMessage()], 500);
        }
    }
}