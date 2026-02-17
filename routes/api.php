<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartnerStatusController;

Route::middleware('api.key')->post('/update-partner-status', [PartnerStatusController::class, 'updatePartnerStatus']);
