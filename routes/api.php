<?php

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookAuthorController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //Akun
    // Route::controller(UserController::class)->group(function(){
    //     Route::get('/user', 'index');
    //     Route::post('/user/store', 'store');
    //     Route::patch('/user/{id}/update', 'update');
    //     Route::get('/user/{id}','show');
    //     Route::delete('/user/{id}', 'destroy');
    // });

    // Route untuk SEMUA user yang sudah login
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']); // User melihat profil sendiri
    Route::apiResource('loan', LoanController::class); // User mengelola peminjaman sendiri

    // Group untuk route yang HANYA bisa diakses oleh SUPERADMIN & ADMIN
    Route::middleware('role:superadmin,pustakawan')->group(function () {
        // Manajemen data master oleh admin
        Route::apiResource('author', AuthorController::class);
        Route::apiResource('book', BookController::class);
        Route::apiResource('book_author', BookAuthorController::class);
    });

    // Group untuk route yang HANYA bisa diakses oleh SUPERADMIN
    Route::middleware('role:superadmin')->group(function () {
        // Manajemen semua user hanya oleh superadmin
        Route::apiResource('user', UserController::class)->except(['profile']);
    });
});
