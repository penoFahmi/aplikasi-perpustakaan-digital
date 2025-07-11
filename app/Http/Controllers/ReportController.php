<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Laporan transaksi peminjaman berdasarkan rentang tanggal.
     */
    public function loans(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $loans = Loan::with(['user:id,name', 'books:id,title'])
            ->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()])
            ->latest()
            ->get();

        return response()->json($loans);
    }

    /**
     * Laporan peminjaman yang dikembalikan terlambat.
     */
    public function overdueReturns(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $overdueLoans = Loan::with('user:id,name')
            ->where('status_peminjaman', 'Dikembalikan')
            ->where('denda', '>', 0)
            ->whereBetween('updated_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()])
            ->get()
            ->map(function ($loan) {
                // Menghitung hari terlambat untuk laporan
                $dueDate = Carbon::parse($loan->tanggal_kembali)->startOfDay();
                $returnDate = Carbon::parse($loan->updated_at)->startOfDay();
                $loan->hari_terlambat = $returnDate->diffInDays($dueDate);
                return $loan;
            });

        return response()->json($overdueLoans);
    }

    /**
     * Laporan aktivitas anggota (total pinjam & denda).
     */
    public function memberActivity(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $members = User::whereHas('role', function ($query) {
                $query->where('name', 'user');
            })
            ->withCount(['loans' => function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
            }])
            ->withSum(['loans as total_fines' => function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
            }], 'denda')
            ->orderByDesc('loans_count')
            ->get();

        return response()->json($members);
    }

    /**
     * Laporan inventaris dan status stok buku.
     */
    public function bookInventory(): JsonResponse
    {
        $books = Book::withCount(['loans as active_loans_count' => function ($query) {
            $query->where('status_peminjaman', 'Dipinjam');
        }])->get()->map(function ($book) {
            $book->available_stock = $book->stock - $book->active_loans_count;
            return $book;
        });

        return response()->json($books);
    }

    /**
     * Laporan pendapatan denda per periode.
     */
    public function fines(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $finesReport = Loan::with('user:id,name')
            ->where('denda', '>', 0)
            ->whereBetween('updated_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()])
            ->select('id', 'user_id', 'denda', 'status_denda', 'updated_at as payment_date')
            ->get();

        return response()->json($finesReport);
    }
}
