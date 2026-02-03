<?php

use App\Http\Controllers\Api\TodoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('todos', TodoController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
