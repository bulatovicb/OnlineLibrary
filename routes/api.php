<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AuthorController;

Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::patch('/user/update', [UserController::class, 'update']);
    Route::post('user/update-profile-picture', [UserController::class, 'updateProfilePicture']);

    Route::middleware(['librarian'])->group(function () {
        Route::post('create', [UserController::class, 'create']);
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::get('/users/{user}/profile-picture', [UserController::class, 'profilePicture'])->name('user.profilePicture');
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::post('/authors/create', [AuthorController::class, 'create']);
    });
});
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

