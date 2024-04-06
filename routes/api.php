<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/registration', [RegistrationController::class, 'register']);

Route::post('/sanctum/token', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/logout-device', [LoginController::class, 'logoutFromDevice']);
    Route::post('/logout-all', [LoginController::class, 'logoutFromAll']);
    Route::post('/delete-account', [LoginController::class, 'deleteAccount']);
    Route::get('/registered-devices', [LoginController::class, 'tokens']);

    Route::patch('/update-profile', [ProfileController::class, 'updateProfile']);
    Route::patch('/update-password', [ProfileController::class, 'updatePassword']);
});

Route::middleware(['auth:sanctum'])->controller(ChatController::class)->group(function () {
    Route::get('/chats', 'index');

    Route::post('/chats', 'store');
    Route::patch('/chat/{chat}', 'update');

    Route::put('/chat/{chat}/join', 'join');
    Route::put('/chat/{chat}/leave', 'leave');

    Route::patch('/chat/{chat}', 'update');
});

Route::middleware(['auth:sanctum'])->controller(MessageController::class)->group(function () {
    Route::get('/chat/{chat}/messages', 'index');

    Route::post('/chat/{chat}/messages', 'store');

    Route::delete('/message/{message}', 'destroy');
});
