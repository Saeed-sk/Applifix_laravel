<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminController;


Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [\App\Http\Controllers\API\AuthController::class, 'me']);
    Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout']);
    Route::post('/user/profile', [\App\Http\Controllers\API\AuthController::class, 'profile']);
    Route::post('/user/password', [\App\Http\Controllers\API\AuthController::class, 'updatePassword']);

    Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function (){
        Route::post('/topics', [\App\Http\Controllers\API\TopicController::class, 'create']);
        Route::post('/topics/{id}', [\App\Http\Controllers\API\TopicController::class, 'update']);
        Route::delete('/topics', [\App\Http\Controllers\API\TopicController::class, 'destroy']);
    });

    Route::get('/chat/{chat}', [\App\Http\Controllers\API\ChatController::class, 'show']);
    Route::post('/chat/delete/{chat}',[\App\Http\Controllers\API\ChatController::class, 'destroy']);
    Route::get('/chats/search', [\App\Http\Controllers\API\ChatController::class, 'searchQuery']);
    Route::get('/chats',[\App\Http\Controllers\API\ChatController::class, 'index']);
});



//Route::post('/refresh', [\App\Http\Controllers\API\AuthController::class, 'refresh'])->middleware('auth:api');

Route::get('/topics', [\App\Http\Controllers\API\TopicController::class, 'index']);
Route::middleware([\App\Http\Middleware\LimitGuestApiWithDB::class])->group(function () {
    Route::post('/chat', [\App\Http\Controllers\API\ChatController::class, 'create']);
    Route::post('/new/chat/topic', [\App\Http\Controllers\API\ChatController::class, 'createWithTopic']);
});

// Admin routes
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/error-logs', [AdminController::class, 'errorLogs']);
    Route::get('/visits', [AdminController::class, 'visits']);
});

