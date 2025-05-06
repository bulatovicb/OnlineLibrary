<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('create', [UserController::class, 'create'])->middleware('auth:sanctum', 'librarian');
Route::get('users/{username}', [UserController::class, 'show'])->middleware('auth:sanctum', 'librarian');
Route::get('/users/{username}/profile-picture', [UserController::class, 'profilePicture'])->middleware('auth:sanctum', 'librarian')->name('user.profilePicture');

Route::patch('/user/update', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::post('user/update-profile-picture', [UserController::class, 'updateProfilePicture'])->middleware('auth:sanctum');

Route::get('users', [UserController::class, 'index'])->middleware('auth:sanctum', 'librarian');
Route::delete('users', [UserController::class, 'destroy'])->middleware('auth:sanctum', 'librarian');
