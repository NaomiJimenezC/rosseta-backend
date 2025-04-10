<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CommentController; // Asegúrate de importar el controlador de comentarios

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
});