<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::get('/organization/{organization}/reviews', [OrganizationController::class, 'reviews']);
    Route::post('/organization/start', [OrganizationController::class, 'start']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
});
