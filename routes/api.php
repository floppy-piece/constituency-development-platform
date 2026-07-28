<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Geolocation\CitizenLocationController;
use App\Http\Controllers\MP\MpAuthController;
use App\Http\Controllers\MP\MpController;
use App\Http\Controllers\MP\ProfileController;
use App\Http\Controllers\MP\AnalyticsController;
use App\Http\Controllers\Messages\TelegramWebhookController;
use App\Http\Controllers\Messages\WhatsAppWebhookController;


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
        Route::post('/compare-proposals', [MpController::class, 'compareProposals']);
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
        // Resolve Request API Endpoint (for Alpine.js)
        // GET request details
        Route::get('/requests/{id}', function ($id) {
            $request = ConstituencyRequest::findOrFail($id);
            return response()->json($request);
        })->whereNumber('id');

        // POST resolve request
        Route::post('/requests/{id}/resolve', function ($id) {
            $mp = auth('mp_api')->user();
            
            $requestItem = \App\Models\ConstituencyRequest::where('mp_id', $mp->mp_id)
                ->findOrFail($id);
            
            $requestItem->update(['status' => 'resolved']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Request marked as resolved!'
            ]);
        })->whereNumber('id');
    });
});