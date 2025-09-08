<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookImportController;
use App\Http\Controllers\BookOptionsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReservationController;
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
        Route::delete('users', [UserController::class, 'destroy']);

        Route::post('/authors/create', [AuthorController::class, 'create']);
        Route::get('/authors/{author}/picture', [AuthorController::class, 'authorsPicture'])->name('author.authorsPicture');
        Route::patch('/authors/{author}/update', [AuthorController::class, 'update']);
        Route::post('/authors/{author}/update-picture', [AuthorController::class, 'updatePicture']);
        Route::delete('/authors/{author}/destroy', [AuthorController::class, 'destroy']);

        Route::post('/categories/create', [CategoryController::class, 'create']);
        Route::get('/categories/{category}/icon', [CategoryController::class, 'categoryIcon'])->name('category.categoryIcon');
        Route::patch('/categories/{category}/update', [CategoryController::class, 'update']);
        Route::post('/categories/{category}/update-icon', [CategoryController::class, 'updateIcon']);
        Route::delete('/categories/{category}/destroy', [CategoryController::class, 'destroy']);

        Route::post('/books/create', [BookController::class, 'create']);
        Route::get('/books/{book}/picture', [BookController::class, 'bookPicture'])->name('book.bookPicture');
        Route::patch('/books/{book}/update', [BookController::class, 'update']);
        Route::post('/books/{book}/update-cover', [BookController::class, 'updateCover']);
        Route::delete('/books/{book}/destroy', [BookController::class, 'destroy']);

        Route::get('/book-options', [BookOptionsController::class, 'index']);

        Route::post('/genres/create', [GenreController::class, 'create']);
        Route::patch('/genres/{genre}/update', [GenreController::class, 'update']);
        Route::delete('genres/{genre}/destroy', [GenreController::class, 'destroy']);

        Route::get('/policies', [PolicyController::class, 'index']);
        Route::patch('policies/{policy}', [PolicyController::class, 'update']);

        Route::post('books/{id}/discard', [RentalController::class, 'discard']);

        Route::get('/rentals/summary', [RentalController::class, 'rentalSummary']);

        Route::post('/rentals', [RentalController::class, 'store']);
        Route::post('/rentals/{id}/return', [RentalController::class, 'returnBook']);

        Route::post('/import-books', [BookImportController::class, 'import']);
        Route::post('/import-books-batch', [BookImportController::class, 'importBatch']);

        Route::post('/publishers/create', [PublisherController::class, 'create']);
        Route::get('/publishers/{publisher}/logo', [PublisherController::class, 'publisherLogo'])->name('publisher.publisherLogo');
        Route::patch('/publishers/{publisher}/update', [PublisherController::class, 'update']);
        Route::post('/publishers/{publisher}/update-logo', [PublisherController::class, 'updateLogo']);
        Route::delete('/publishers/{publisher}', [PublisherController::class, 'destroy']);

        Route::post('/reservations/{id}/confirm', [ReservationController::class, 'confirm']);
        Route::post('/reservations/{id}/reject', [ReservationController::class, 'reject']);
    });

    Route::get('users/{user}', [UserController::class, 'show']);
    Route::get('/users/{user}/profile-picture', [UserController::class, 'profilePicture'])->name('user.profilePicture');
    Route::post('users/change-password', [PasswordResetController::class, 'changePassword']);

    Route::get('/authors/{author}', [AuthorController::class, 'show']);
    Route::get('/authors', [AuthorController::class, 'index']);

    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/books/{book}', [BookController::class, 'show']);
    Route::get('/books', [BookController::class, 'index']);

    Route::get('/genres/{genre}', [GenreController::class, 'show']);
    Route::get('/genres', [GenreController::class, 'index']);

    Route::get('/publishers/{publisher}', [PublisherController::class, 'show']);
    Route::get('/publishers', [PublisherController::class, 'index']);

    Route::get('/rentals/active', [RentalController::class, 'indexRented']);
    Route::get('/rentals/returned', [RentalController::class, 'indexReturned']);
    Route::get('/rentals/overdue', [RentalController::class, 'indexOverdue']);
    Route::get('/rentals/{rental}', [RentalController::class, 'show']);

    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/active', [ReservationController::class, 'active']);
    Route::get('/reservations/archived', [ReservationController::class, 'archived']);
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
});
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

