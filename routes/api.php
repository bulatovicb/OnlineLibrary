<?php

use Illuminate\Support\Facades\Route;

Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('create', [\App\Http\Controllers\UserController::class, 'create'])->middleware('auth:sanctum', 'librarian');
Route::get('users/{username}', [\App\Http\Controllers\UserController::class, 'show'])->middleware('auth:sanctum', 'librarian');
Route::get('/users/{username}/profile-picture', [\App\Http\Controllers\UserController::class, 'profilePicture'])->middleware('auth:sanctum', 'librarian')->name('user.profilePicture');

Route::patch('/user/update', [\App\Http\Controllers\UserController::class, 'update'])->middleware('auth:sanctum');
Route::post('user/update-profile-picture', [\App\Http\Controllers\UserController::class, 'updateProfilePicture'])->middleware('auth:sanctum');

Route::get('users', [\App\Http\Controllers\UserController::class, 'index'])->middleware('auth:sanctum', 'librarian');
Route::delete('users/{username}', [\App\Http\Controllers\UserController::class, 'destroy'])->middleware('auth:sanctum', 'librarian');
