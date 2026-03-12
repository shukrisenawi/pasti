<?php

use App\Http\Controllers\Api\N8nNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('n8n')->middleware('n8n.token')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true]));
    Route::get('/notifications', [N8nNotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [N8nNotificationController::class, 'markAsRead']);
});

