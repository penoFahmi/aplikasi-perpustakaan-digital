<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Laporan transaksi peminjaman berdasarkan rentang tanggal.
     */
    public function loans(Request $request): JsonResponse
    {
        // PERUBAHAN 1: Validasi dibuat tidak wajib (opsional).
        $request->validate([
            'start_date' => 'sometimes|required_with:end_date|date',
            'end_date' => 'sometimes|required_with:start_date|date|after_or_equal:start_date',
        ]);

        $query = Loan::with(['user:id,name', 'books:id,title']);

        // PERUBAHAN 2: Terapkan filter tanggal hanya jika ada di request.
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
        }

        $loans = $query->latest()->get();

        // PERUBAHAN 3: Transformasi data agar sesuai dengan frontend.
        $reportData = $loans->map(function ($loan) {
            return [
                'member_name' => $loan->user->name ?? 'N/A',
                // Gabungkan judul buku jika ada lebih dari satu
                'book_title' => $loan->books->pluck('title')->implode(', '),
                'loan_date' => Carbon::parse($loan->created_at)->format('Y-m-d'),
                'due_date' => Carbon::parse($loan->tanggal_kembali)->format('Y-m-d'),
                'status' => $loan->status_peminjaman,
            ];
        });

        return response()->json($reportData);
    }

    /**
     * Laporan peminjaman yang dikembalikan terlambat.
     */
    public function overdueReturns(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|required_with:end_date|date',
            'end_date' => 'sometimes|required_with:start_date|date|after_or_equal:start_date',
        ]);

        $query = Loan::with(['user:id,name', 'books:id,title'])
            ->where('status_peminjaman', 'Dikembalikan')
            ->where('denda', '>', 0);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('updated_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
        }

        $overdueLoans = $query->get();

        $reportData = $overdueLoans->map(function ($loan) {
            $dueDate = Carbon::parse($loan->tanggal_kembali)->startOfDay();
            $returnDate = Carbon::parse($loan->updated_at)->startOfDay();
            return [
                'member_name' => $loan->user->name ?? 'N/A',
                'book_title' => $loan->books->pluck('title')->implode(', '),
                'return_date' => $returnDate->format('Y-m-d'),
                'days_overdue' => $returnDate->diffInDays($dueDate),
            ];
        });

        return response()->json($reportData);
    }

    /**
     * Laporan aktivitas anggota (total pinjam & denda).
     */
    public function memberActivity(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|required_with:end_date|date',
            'end_date' => 'sometimes|required_with:start_date|date|after_or_equal:start_date',
        ]);

        $query = User::whereHas('role', function ($q) {
            $q->where('name', 'user');
        });

        $query->withCount(['loans as total_loans' => function ($q) use ($request) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $q->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
            }
        }])->withSum(['loans as total_fines' => function ($q) use ($request) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $q->whereBetween('created_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
            }
        }], 'denda');

        $members = $query->orderByDesc('total_loans')->get();

        $reportData = $members->map(function($member) {
            return [
                'member_name' => $member->name,
                'total_loans' => $member->total_loans,
                'total_fines' => (int) $member->total_fines, // Casting ke integer
            ];
        });

        return response()->json($reportData);
    }

    /**
     * Laporan inventaris dan status stok buku.
     */
    public function bookInventory(): JsonResponse
    {

        $books = Book::withCount(['loans as active_loans_count' => function ($query) {
            $query->where('status_peminjaman', 'Dipinjam');
        }])->get();

        $reportData = $books->map(function ($book) {
            return [
                'title' => $book->title,
                'total_stock' => $book->stock,
                'available_stock' => $book->stock - $book->active_loans_count,
            ];
        });

        return response()->json($reportData);
    }

    /**
     * Laporan pendapatan denda per periode.
     */
    public function fines(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|required_with:end_date|date',
            'end_date' => 'sometimes|required_with:start_date|date|after_or_equal:start_date',
        ]);

        $query = Loan::with(['user:id,name', 'books:id,title'])
            ->where('denda', '>', 0);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('updated_at', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
        }

        $finesReport = $query->get();

        $reportData = $finesReport->map(function($fine) {
            return [
                'member_name' => $fine->user->name ?? 'N/A',
                'book_title' => $fine->books->pluck('title')->implode(', '),
                'amount' => $fine->denda,
                'paid_at' => Carbon::parse($fine->updated_at)->format('Y-m-d'),
            ];
        });

        return response()->json($reportData);
    }
    public function exportLoansExcel()
    {
        return Excel::download(new LoansReportExport, 'laporan-peminjaman.xlsx');
    }
    public function exportLoansPdf()
    {
        // 1. Ambil data yang diperlukan
        $data['loans'] = Loan::with(['user:id,name', 'books:id,title'])->get();

        // 2. Load view dan data ke PDF
        $pdf = PDF::loadView('reports.loans_pdf', $data);

        // 3. Unduh file PDF
        return $pdf->download('laporan-peminjaman.pdf');
    }
}
