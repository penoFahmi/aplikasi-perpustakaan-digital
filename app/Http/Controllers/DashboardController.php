<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Mengambil data statistik utama untuk kartu di dasbor.
     */
    public function stats(): JsonResponse
    {
        $activeLoans = Loan::where('status_peminjaman', 'Dipinjam')->count();

        $overdueLoans = Loan::where('status_peminjaman', 'Dipinjam')
                            ->whereDate('tanggal_kembali', '<', now())
                            ->count();

        $unpaidFines = Loan::where('status_denda', 'Belum Lunas')->sum('denda');

        // Asumsi 'peminjam' adalah peran untuk anggota biasa
        $totalMembers = User::whereHas('role', function ($query) {
            $query->where('name', 'user');
        })->count();

        return response()->json([
            'active_loans' => $activeLoans,
            'overdue_loans' => $overdueLoans,
            'unpaid_fines' => (int) $unpaidFines,
            'total_members' => $totalMembers,
        ]);
    }

    /**
     * Mengambil daftar 5 peminjaman yang paling telat.
     */
    public function overdueLoans(): JsonResponse
    {
        $loans = Loan::with('user:id,name')
                     ->where('status_peminjaman', 'Dipinjam')
                     ->whereDate('tanggal_kembali', '<', now())
                     ->orderBy('tanggal_kembali', 'asc') // Urutkan dari yang paling lama telat
                     ->limit(5)
                     ->get()
                     ->map(function ($loan) {
                         $loan->hari_terlambat = Carbon::now()->diffInDays(Carbon::parse($loan->tanggal_kembali));
                         return $loan;
                     });

        return response()->json($loans);
    }

    /**
     * Mengambil data untuk grafik aktivitas peminjaman 7 hari terakhir.
     */
    public function loanActivity(): JsonResponse
    {
        $labels = [];
        $loansData = [];
        $returnsData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M'); // Format tanggal (e.g., 09 Jul)

            $loansData[] = Loan::whereDate('created_at', $date)->count();

            $returnsData[] = Loan::where('status_peminjaman', 'Dikembalikan')
                                 ->whereDate('updated_at', $date) // Asumsi tgl kembali adalah saat status di-update
                                 ->count();
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Dipinjam',
                    'data' => $loansData,
                ],
                [
                    'label' => 'Dikembalikan',
                    'data' => $returnsData,
                ]
            ]
        ]);
    }

    /**
     * Mengambil 5 buku paling populer (paling sering dipinjam) bulan ini.
     */
    public function popularBooks(): JsonResponse
    {
        $popularBooks = DB::table('books')
            ->join('loan_details', 'books.id', '=', 'loan_details.book_id')
            ->join('loans', 'loan_details.loan_id', '=', 'loans.id')
            ->select('books.title', DB::raw('COUNT(loan_details.book_id) as total_loans'))
            ->whereMonth('loans.created_at', now()->month)
            ->whereYear('loans.created_at', now()->year)
            ->groupBy('books.title')
            ->orderByDesc('total_loans')
            ->limit(5)
            ->get();

        return response()->json($popularBooks);
    }
}
