<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('unread/count', [NotificationController::class, 'unreadCount']);
    Route::patch('read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('{id}/read', [NotificationController::class, 'markRead'])->where('id', '[0-9]+');
});
