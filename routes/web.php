<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Models\Room;
use Illuminate\Support\Facades\Route;

// Route model binding: resolve room berdasarkan kolom code (bukan id).
Route::bind('room', fn (string $code) => Room::where('code', $code)->firstOrFail());

Route::get('/', [RoomController::class, 'index'])->name('home');
Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

Route::middleware('room.expired')->group(function () {
    Route::get('/room/{room}', [RoomController::class, 'show'])->name('room.show');
    Route::post('/room/{room}/join', [RoomController::class, 'join'])->name('room.join');

    Route::prefix('/room/{room}')->name('room.')->group(function () {
        Route::get('/messages', [ChatController::class, 'index'])->name('messages.index');
        Route::post('/messages', [ChatController::class, 'store'])
            ->middleware('throttle:chat')
            ->name('messages.store');
        Route::post('/typing', [ChatController::class, 'typing'])->name('typing');
        Route::get('/typing/status', [ChatController::class, 'typingStatus'])->name('typing.status');
        Route::get('/users', [ChatController::class, 'presence'])->name('presence');
        Route::post('/leave', [ChatController::class, 'leave'])->name('leave');
    });
});
