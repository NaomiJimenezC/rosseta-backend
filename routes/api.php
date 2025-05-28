<?php

use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PostFolloweeController;
use App\Http\Controllers\Api\V1\SearcherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v1'], function () {
    // Rutas públicas
    Route::get('/search', [SearcherController::class, 'search'])->name('search');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login',    [AuthController::class, 'login'])->name('login');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email');

    // Todas las rutas de aquí abajo requieren autenticación
    Route::middleware('auth:sanctum')->group(function () {
        // Usuario actual
        Route::get('/user', fn(Request $r) => $r->user());

        // Auth
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Posts
        Route::get('/users/{user_id}/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/{post_id}',      [PostController::class, 'getPost'])->name('posts.show');
        Route::post('/posts',               [PostController::class, 'store'])->name('posts.store');
        Route::delete('/posts/{post_id}',   [PostController::class, 'delete'])->name('posts.destroy');

        // Comments
        Route::get('/comments',   [CommentController::class, 'index'])->name('comments.index');
        Route::post('/comments',  [CommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments',[CommentController::class, 'delete'])->name('comments.destroy');

        // Likes
        Route::get('/users/liked',     [LikeController::class, 'index'])->name('likes.index');
        Route::get('/posts/likes',     [LikeController::class, 'show'])->name('likes.show');
        Route::post('/posts/{post}/like',    [LikeController::class, 'store'])->name('likes.store');
        Route::delete('/posts/{post}/dislike',[LikeController::class, 'destroy'])->name('likes.destroy');
        Route::get('/posts/{post}/liked',     [LikeController::class, 'hasLiked'])->name('posts.hasLiked');

        // Follows
        Route::post('/users/{user}/follow',    [FollowController::class, 'follow'])->name('follows.store');
        Route::delete('/users/{user}/unfollow',[FollowController::class, 'unfollow'])->name('follows.destroy');
        Route::get('/users/{user}/followers',  [FollowController::class, 'getFollowers'])->name('follows.followers');
        Route::get('/users/{user}/following',  [FollowController::class, 'getFollowing'])->name('follows.following');
        Route::get('/users/{user}/is-following',[FollowController::class, 'isFollowing'])->name('follows.isFollowing');

        // Profile
        Route::get('/profile',                    [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/users/{identifier}',         [ProfileController::class, 'showUser'])->name('users.show');
        Route::patch('/profile',                  [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password',         [ProfileController::class, 'changePassword'])->name('profile.password.update');
        Route::patch('/profile/picture',          [ProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
        Route::delete('/profile/picture',         [ProfileController::class, 'deleteProfilePicture'])->name('profile.picture.destroy');
        Route::delete('/profile',                 [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Feed
        Route::get('/feed', [PostFolloweeController::class, 'index'])->name('feed.index');

        // Conversations & Chat
        Route::get('/conversations',                           [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/unread-count',              [ConversationController::class, 'unreadCount'])->name('conversations.unreadCount');
        Route::get('/conversations/{conversation}',            [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{other}',                  [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}/messages',   [MessageController::class, 'index'])->name('conversations.messages.index');
        Route::post('/conversations/{conversation}/messages',  [MessageController::class, 'store'])->name('conversations.messages.store');

        // Notifications
        Route::get('/notifications',           [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count',[NotificationController::class, 'unreadCount'])->name('notifications.unread.count');
        Route::post('/notifications/mark-read', [NotificationController::class, 'markAllRead'])->name('notifications.markRead');
    });
});
