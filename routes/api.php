<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartnerStatusController;

Route::middleware('api.key')->post('/update-partner-status', [PartnerStatusController::class, 'updatePartnerStatus']);

Route::prefix('v1')->group(function () {
    // Public Token Exchange Endpoint (Client ID + Client Secret)
    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'issueToken']);

    // Protected Partner Endpoints (Sanctum Bearer Token)
    Route::middleware('api.sanctum')->group(function () {
        Route::get('/contracts', [\App\Http\Controllers\Api\V1\ContractApiController::class, 'index']);
        Route::get('/inbound-slots', [\App\Http\Controllers\Api\V1\SlotApiController::class, 'inboundSlots']);
        Route::get('/stock', [\App\Http\Controllers\Api\V1\StockApiController::class, 'index']);
        Route::get('/outbound-slots', [\App\Http\Controllers\Api\V1\SlotApiController::class, 'outboundSlots']);
        Route::post('/inbound-requests', [\App\Http\Controllers\Api\V1\InboundApiController::class, 'store']);
        Route::post('/outbound-requests', [\App\Http\Controllers\Api\V1\OutboundApiController::class, 'store']);
    });
});
