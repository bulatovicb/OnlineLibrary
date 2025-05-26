<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/authors/{author}', [AuthorController::class, 'show']);
        Route::get('/authors/{author}/picture', [AuthorController::class, 'authorsPicture'])->name('author.authorsPicture');
        Route::get('/authors', [AuthorController::class, 'index']);
        Route::patch('/authors/{author}/update', [AuthorController::class, 'update']);
        Route::post('/authors/{author}/update-picture', [AuthorController::class, 'updatePicture']);
        Route::delete('/authors/{author}/destroy', [AuthorController::class, 'destroy']);

        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/categories/{category}/icon', [CategoryController::class, 'categoryIcon'])->name('category.categoryIcon');
    });
});
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

