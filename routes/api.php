<?php

use App\Http\Controllers\Api\N8nNotificationController;
use App\Http\Controllers\Api\GuruMobileApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('n8n')->middleware('n8n.token')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true]));
    Route::get('/notifications', [N8nNotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [N8nNotificationController::class, 'markAsRead']);
});

Route::prefix('guru')->group(function () {
    Route::post('/login', [GuruMobileApiController::class, 'login'])->name('api.guru.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [GuruMobileApiController::class, 'profile'])->name('api.guru.profile');
        Route::post('/profile/complete', [GuruMobileApiController::class, 'completeProfile'])->name('api.guru.complete-profile');
        Route::get('/pasti-options', [GuruMobileApiController::class, 'pastiOptions'])->name('api.guru.pasti-options');
        Route::post('/logout', [GuruMobileApiController::class, 'logout'])->name('api.guru.logout');

        Route::middleware('guru.profile.completed')->group(function () {
            Route::get('/kpi', [GuruMobileApiController::class, 'kpi']);
            Route::get('/leave-notices', [GuruMobileApiController::class, 'leaveNotices']);
            Route::post('/leave-notices', [GuruMobileApiController::class, 'storeLeaveNotice']);
            Route::post('/programs/{program}/status', [GuruMobileApiController::class, 'updateProgramStatus']);
        });
    });
});
