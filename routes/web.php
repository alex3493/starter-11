<?php

use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});
//
//Route::get('/dashboard', function () {
//    return Inertia::render('Dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [
        HomeController::class,
        'index',
    ])->name('home');

    Route::get('/chat/{chat}', [
        HomeController::class,
        'show',
    ])->name('chat');

    Route::get('/chat/{chat}/read', [
        HomeController::class,
        'read',
    ])->name('read-chat');

    Route::post('/chats', [
        HomeController::class,
        'store',
    ])->name('store-chat');

    Route::patch('/chat/{chat}', [
        HomeController::class,
        'update',
    ])->name('update-chat');

    Route::delete('/chat/{chat}', [
        HomeController::class,
        'delete',
    ])->name('delete-chat');

    Route::put('/chat/{chat}/join', [
        HomeController::class,
        'join',
    ])->name('join-chat');

    Route::put('/chat/{chat}/leave', [
        HomeController::class,
        'leave',
    ])->name('join-chat');

    Route::post('/chat/{chat}/messages', [
        ChatMessageController::class,
        'store',
    ])->name('store-chat-message');

    Route::patch('/chat/{chat}/message/{message}', [
        ChatMessageController::class,
        'update',
    ])->name('update-chat-message');

    Route::delete('/chat/{chat}/message/{message}', [
        ChatMessageController::class,
        'delete',
    ])->name('delete-chat-message');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
