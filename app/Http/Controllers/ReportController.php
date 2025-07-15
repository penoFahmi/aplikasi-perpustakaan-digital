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
use Barryvdh\DomPDF\Facade\Pdf;

use App\Exports\LoansReportExport;
use App\Exports\OverdueReturnsReportExport;
use App\Exports\FinesReportExport;
use App\Exports\MemberActivityReportExport;
use App\Exports\BookInventoryReportExport;

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

    /**
     * Menangani semua permintaan ekspor laporan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $reportType Jenis laporan (loans, fines, dll.)
     * @param  string  $format Format ekspor (pdf, excel)
     * @return \Illuminate\Http\Response
     */
    public function exportReport(Request $request, $reportType, $format)
    {
        // Validasi dan ambil parameter tanggal dari request
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : null;
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : null;

        $exportClass = null;
        $viewName = null;
        $viewData = [];

        // Logika untuk setiap jenis laporan
        switch ($reportType) {
            case 'loans':
                $query = Loan::with(['user:id,name', 'books:id,title']);
                if ($startDate && $endDate) {
                    // Filter berdasarkan tanggal peminjaman
                    $query->whereBetween('loan_date', [$startDate, $endDate]);
                }
                $viewData['loans'] = $query->get();
                $exportClass = new LoansReportExport($viewData['loans']);
                $viewName = 'reports.loans_pdf';
                break;

            case 'overdue-returns':
                $query = Loan::with(['user:id,name', 'books:id,title'])
                    ->where('status_peminjaman', 'Dikembalikan')
                    ->whereNotNull('actual_return_date')
                    ->whereRaw('DATEDIFF(actual_return_date, due_date) > 0');

                if ($startDate && $endDate) {
                    // Filter berdasarkan tanggal pengembalian
                    $query->whereBetween('actual_return_date', [$startDate, $endDate]);
                }
                $viewData['overdue_returns'] = $query->get()->map(function ($loan) {
                    // Menghitung hari keterlambatan
                    $loan->days_overdue = Carbon::parse($loan->actual_return_date)->diffInDays(Carbon::parse($loan->due_date));
                    return $loan;
                });
                $exportClass = new OverdueReturnsReportExport($viewData['overdue_returns']);
                $viewName = 'reports.overdue_returns_pdf';
                break;

            case 'fines':
                $query = Loan::with(['user:id,name', 'books:id,title'])
                    ->where('denda', '>', 0);

                if ($startDate && $endDate) {
                    // Filter berdasarkan tanggal pengembalian yang menghasilkan denda
                    $query->whereBetween('actual_return_date', [$startDate, $endDate]);
                }
                $viewData['fines'] = $query->get();
                $exportClass = new FinesReportExport($viewData['fines']);
                $viewName = 'reports.fines_pdf';
                break;

            case 'member-activity':
                // Query ini lebih kompleks, kita agregat data per user
                $query = User::withCount(['loans as total_loans' => function ($query) use ($startDate, $endDate) {
                        if ($startDate && $endDate) {
                            $query->whereBetween('loan_date', [$startDate, $endDate]);
                        }
                    }])
                    ->withSum(['loans as total_fines' => function ($query) use ($startDate, $endDate) {
                        if ($startDate && $endDate) {
                            $query->whereBetween('actual_return_date', [$startDate, $endDate]);
                        }
                    }], 'denda');

                $viewData['members'] = $query->having('total_loans', '>', 0)->orHaving('total_fines', '>', 0)->get();
                $exportClass = new MemberActivityReportExport($viewData['members']);
                $viewName = 'reports.member_activity_pdf';
                break;

            case 'book-inventory':
                // Laporan ini tidak memakai filter tanggal
                $viewData['books'] = Book::withCount(['loans as borrowed_count' => function ($query) {
                    $query->where('status_peminjaman', 'Dipinjam');
                }])->get()->map(function ($book) {
                    $book->available_stock = $book->stock - $book->borrowed_count;
                    return $book;
                });
                $exportClass = new BookInventoryReportExport($viewData['books']);
                $viewName = 'reports.book_inventory_pdf';
                break;

            default:
                return response()->json(['message' => 'Tipe laporan tidak valid.'], 404);
        }

        // Buat nama file dinamis
        $fileName = "laporan-{$reportType}-" . now()->format('Y-m-d') . ($format === 'excel' ? '.xlsx' : '.pdf');

        // Ekspor berdasarkan format yang diminta
        if ($format === 'excel') {
            return Excel::download($exportClass, $fileName);
        }

        if ($format === 'pdf') {
            // Pastikan Anda sudah membuat file view blade untuk setiap laporan
            $pdf = PDF::loadView($viewName, $viewData);
            return $pdf->download($fileName);
        }
    }
}
