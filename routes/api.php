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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

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
        Route::post('loan/{id}/return', [LoanController::class, 'return']);
        Route::post('loan/{id}/pay', [LoanController::class, 'payFine']);
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'stats']);
            Route::get('/overdue-loans', [DashboardController::class, 'overdueLoans']);
            Route::get('/loan-activity', [DashboardController::class, 'loanActivity']);
            Route::get('/popular-books', [DashboardController::class, 'popularBooks']);
        });
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/loans', [ReportController::class, 'loans'])->name('loans');
            Route::get('/overdue-returns', [ReportController::class, 'overdueReturns'])->name('overdue-returns');
            Route::get('/member-activity', [ReportController::class, 'memberActivity'])->name('member-activity');
            Route::get('/book-inventory', [ReportController::class, 'bookInventory'])->name('book-inventory');
            Route::get('/fines', [ReportController::class, 'fines'])->name('fines');
        });
    });
});
