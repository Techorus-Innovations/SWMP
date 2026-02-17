<?php

use Illuminate\Support\Facades\Route;
use App\Services\SapService;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test', function () {
    $sapService=new SapService();
    $materials = $sapService->getCsrfToken();
    return $materials;
});
