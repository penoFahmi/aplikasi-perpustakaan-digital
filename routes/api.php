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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::patch('/user/profile', [UserController::class, 'profile']);
    Route::apiResource('loan', LoanController::class);
    Route::apiResource('user', UserController::class);
    Route::delete('/user/{user}', [UserController::class, 'deleteAccount']);

    Route::middleware('role:superadmin,pustakawan')->group(function () {
        Route::apiResource('author', AuthorController::class);
        Route::apiResource('book', BookController::class);
        Route::apiResource('book_author', BookAuthorController::class);
        Route::apiResource('loan', LoanController::class);

    });
});
