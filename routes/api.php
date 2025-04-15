<?php

use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\LikeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProfileController; // Asegúrate de importar el controlador de perfil

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function () {
    // Rutas de autenticación
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email');

    // Rutas para los posts
    Route::get('/users/{user_id}/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post_id}', [PostController::class, 'getPost'])->name('posts.show');
    Route::post('/posts', [PostController::class, 'store'])->middleware('auth:sanctum')->name('posts.store');
    Route::delete('/posts/{post_id}', [PostController::class, 'delete'])->middleware('auth:sanctum')->name('posts.destroy');

    // Rutas para los comentarios
    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index'); // Obtener comentarios de un post (requiere post_id en la query)
    Route::post('/comments', [CommentController::class, 'store'])->middleware('auth:sanctum')->name('comments.store'); // Crear un nuevo comentario
    Route::delete('/comments', [CommentController::class, 'delete'])->middleware('auth:sanctum')->name('comments.destroy'); // Eliminar un comentario

    // Rutas para los me gustas
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users/liked', [LikeController::class, 'index'])->name('likes.index'); // Get all posts with like information for the authenticated user
        Route::get('/posts/likes', [LikeController::class, 'show']) ->name('likes.show');
        Route::post('/posts/{post}/like', [LikeController::class, 'store'])->name('likes.store'); // Like a specific post
        Route::delete('/posts/{post}/dislike', [LikeController::class, 'destroy'])->name('likes.destroy'); // Unlike a specific post
        Route::get('/posts/{post}/liked', [LikeController::class, 'hasLiked'])->name('posts.hasLiked');
    });

    //Rutas para los follows
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/users/{user}/follow', [FollowController::class, 'follow'])->name('follows.store');
        Route::delete('/users/{user}/unfollow', [FollowController::class, 'unfollow'])->name('follows.destroy');
        Route::get('/users/{user}/followers', [FollowController::class, 'getFollowers'])->name('follows.followers');
        Route::get('/users/{user}/following', [FollowController::class, 'getFollowing'])->name('follows.following');
        Route::get('/users/{user}/is-following', [FollowController::class, 'isFollowing'])->name('follows.isFollowing');
    });

    //Rutas para el perfil del usuario
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/users/{identifier}', [ProfileController::class, 'showUser'])->name('users.show'); // Permite buscar por ID o username
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
        Route::patch('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
        Route::post('/profile/picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
        Route::delete('/profile/picture', [ProfileController::class, 'deleteProfilePicture'])->name('profile.picture.destroy');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

});