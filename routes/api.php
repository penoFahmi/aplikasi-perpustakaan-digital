<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookAuthorController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Rute Publik (Autentikasi) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Rute Terproteksi (Butuh Login) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Rute Profil Pengguna (Untuk diri sendiri) ---
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::patch('/user/profile', [UserController::class, 'updateProfile']); // Lebih eksplisit

    // --- Rute Peminjaman untuk Pengguna Biasa (hanya melihat daftar dan detail) ---
    Route::apiResource('loan', LoanController::class)->only(['index', 'show']);

    // --- Grup Rute Khusus untuk Superadmin & Pustakawan ---
    Route::middleware('role:superadmin,pustakawan')->group(function () {

        // --- Manajemen Resource Inti ---
        Route::apiResource('author', AuthorController::class);
        Route::post('/author/import', [AuthorController::class, 'import']);
        Route::apiResource('book', BookController::class);
        Route::post('/book/import', [BookController::class, 'import']);
        Route::apiResource('book_author', BookAuthorController::class);
        Route::apiResource('user', UserController::class);

        // --- Manajemen Peminjaman (CRUD penuh & aksi tambahan) ---
        Route::apiResource('loan', LoanController::class)->except(['index', 'show']);
        Route::post('loan/{loan}/return', [LoanController::class, 'return']);
        Route::post('loan/{loan}/pay', [LoanController::class, 'payFine']);

        // --- Rute Dashboard ---
        Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
            Route::get('/stats', 'stats');
            Route::get('/overdue-loans', 'overdueLoans');
            Route::get('/loan-activity', 'loanActivity');
            Route::get('/popular-books', 'popularBooks');
        });

        // --- Rute Laporan (Termasuk Ekspor) ---
        Route::prefix('reports')->controller(ReportController::class)->group(function () {
            Route::get('/loans', 'loans');
            Route::get('/overdue-returns', 'overdueReturns');
            Route::get('/member-activity', 'memberActivity');
            Route::get('/book-inventory', 'bookInventory');
            Route::get('/fines', 'fines');
            Route::get('/{reportType}/export/{format}', [ReportController::class, 'exportReport'])->where('format', 'pdf|excel');
        });
    });
});
