<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RentalController;

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

        Route::post('/books/create', [BookController::class, 'create']);
        Route::get('/books/{book}', [BookController::class, 'show']);
        Route::get('/books/{book}/picture', [BookController::class, 'bookPicture'])->name('book.bookPicture');
        Route::patch('/books/{book}/update', [BookController::class, 'update']);
        Route::post('/books/{book}/update-cover', [BookController::class, 'updateCover']);
        Route::delete('/books/{book}/destroy', [BookController::class, 'destroy']);

        Route::get('/genres' , [GenreController::class, 'index']);
        Route::post('/genres/create', [GenreController::class, 'create']);
        Route::get('/genres/{genre}', [GenreController::class, 'show']);
        Route::patch('/genres/{genre}/update', [GenreController::class, 'update']);
        Route::delete('genres/{genre}/destroy', [GenreController::class, 'destroy']);

        Route::get('/policies' , [PolicyController::class, 'index']);
        Route::patch('policies/{policy}', [PolicyController::class, 'update']);

        Route::post('/rentals', [RentalController::class, 'store']);
        Route::get('/rentals/{id}', [RentalController::class, 'show']);

    });
});
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

