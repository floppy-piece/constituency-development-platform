<?php

/*use App\Http\Controllers\WhatsAppWebhookController;
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verifyWebhook']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handleWebhook']);*/
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CitizenLocationController;
use App\Http\Controllers\Api\MpAuthController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\MpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MP\AnalyticsController;
use App\Http\Controllers\WhatsAppWebhookController;


// WhatsApp Webhook Endpoints
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verifyWebhook']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handleWebhook']);


/*
|--------------------------------------------------------------------------
| Public Citizen Endpoints
|--------------------------------------------------------------------------
*/
Route::prefix('citizen')->group(function () {
    Route::post('/detect-mp', [CitizenLocationController::class, 'detectMp']);
    Route::post('/telegram-location-token', [CitizenLocationController::class, 'generateTelegramToken']);
});

/*
|--------------------------------------------------------------------------
| Telegram Webhook Endpoint
|--------------------------------------------------------------------------
*/
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handleWebhook']);

/*
|--------------------------------------------------------------------------
| MP Endpoints (Public Auth & Protected JWT Routes)
|--------------------------------------------------------------------------
*/

Route::prefix('mp')->group(function () {
    
    // Public MP Authentication
    Route::post('/auth/login', [MpAuthController::class, 'login']);

    // JWT Protected MP Routes
    Route::middleware('auth:mp_api')->group(function () {
        
        // Auth management
        Route::post('/auth/logout', [MpAuthController::class, 'logout']);
        Route::post('/auth/refresh', [MpAuthController::class, 'refresh']);

        // Dashboard & Issues Feed
        Route::get('/dashboard', [MpController::class, 'dashboard']);
        Route::get('/issues', [MpController::class, 'getIssues']);
        Route::get('/hotspots', [MpController::class, 'getDemandHotspots']);

        // MP Profile Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/update', [ProfileController::class, 'updateProfile']);
            Route::post('/change-password', [ProfileController::class, 'changePassword']);
        });


        Route::get('/analytics/data', [MpController::class, 'getAnalyticsData']);

        Route::get('/requests/{id}', function ($id) {
            $request = \App\Models\ConstituencyRequest::findOrFail($id);
            return response()->json($request);
        })->whereNumber('id');

        Route::post('/requests/{id}/status', [MpController::class, 'updateStatus'])->whereNumber('id');
        Route::post('/requests/{id}/resolve', [MpController::class, 'markAsResolved'])->whereNumber('id');
    });
});