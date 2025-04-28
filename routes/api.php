<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('librarians', [\App\Http\Controllers\LibrarianController::class, 'store']);
Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');
