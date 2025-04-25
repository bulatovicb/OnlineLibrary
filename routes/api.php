<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('librarians', [\App\Http\Controllers\LibrarianController::class, 'store']);

