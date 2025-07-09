<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function index(): JsonResponse
    {
        $loans = Loan::with(['user','books'])->latest()->get();
        return response()->json($loans, 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $loans = Loan::with(['user','books'])->findOrFail($id);
            return response()->json($loans, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Loan not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        // PERBAIKAN: Validasi untuk array book_ids
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_ids' => 'required|array',
            'book_ids.*' => 'required|exists:books,id', // Memeriksa setiap elemen dalam array
            'tanggal_kembali' => 'required|date|after_or_equal:today',
        ]);
//ini tambah biasa tanpa pengurangan stock pada table buku

        // $loan = Loan::create([
        //     'user_id' => $request->user_id,
        //     'book_id' => $request->book_id,
        // ]);

        // return response()->json([
        //     'message' => 'Loan successfully created.',
        //     'data' => $loan
        // ], 201);

        try {
            // Memulai transaction untuk memastikan data konsisten
            $loan = DB::transaction(function () use ($request) {

                // Buat data peminjaman dengan status default 'Dipinjam'
                $loan = Loan::create([
                    'user_id' => $request->user_id,
                    'tanggal_kembali' => $request->tanggal_kembali,
                ]);

                // 1. Kurangi stok untuk semua buku yang dipilih

                foreach ($request->book_ids as $book_id) {
                    $book = Book::findOrFail($book_id);
                    if ($book->stock < 1) {
                        throw new \Exception("Stok buku '{$book->title}' habis.");
                    }
                    $book->decrement('stock');
                }

                // 3. Lampirkan buku-buku ke peminjaman (mengisi pivot table)
                $loan->books()->attach($request->book_ids);

                return $loan;

            });

            return response()->json([
                'message' => 'Peminjaman berhasil dibuat.',
                'data' => $loan->load(['user', 'books'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

//Ini code lama update biasa tanpa ada pengembalian stock pada tabel buku
    // public function update(Request $request, $id): JsonResponse
    // {
    //     try {
    //         $loan = Loan::findOrFail($id);

    //         $request->validate([
    //             'user_id' => 'sometimes|exists:users,id',
    //             'book_id' => 'sometimes|exists:books,id',
    //         ]);

    //         // Only update the fields provided
    //         $data = $request->only(['user_id', 'book_id']);
    //         $loan->update($data);

    //         return response()->json([
    //             'message' => $loan->wasChanged()
    //                 ? 'Loan data successfully updated.'
    //                 : 'No changes were made.',
    //             'data' => $loan
    //         ], 200);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Loan not found'], 404);
    //     }

    // }


    // public function update(Request $request, $id): JsonResponse
    // {
    //     try {
    //         $loans = Loan::findOrFail($id);

    //         // KONDISI 1: Jika ini adalah permintaan PENGEMBALIAN BUKU
    //         if ($request->has('status') && $request->status === 'Dikembalikan') {
    //             if ($loans->status === 'Dikembalikan') {
    //                 return response()->json(['message' => 'This book has already been returned.'], 400);
    //             }

    //             DB::transaction(function () use ($loans) {
    //                 $loans->update(['status' => 'Dikembalikan']);
    //                 $book = Book::findOrFail($loans->book_id);
    //                 $book->increment('stock');
    //             });

    //             return response()->json([
    //                 'message' => 'Book successfully returned.',
    //                 'data' => $loans->load(['user', 'book']) // Muat ulang relasi
    //             ], 200);
    //         }

    //         // KONDISI 2: Jika ini adalah permintaan EDIT BIASA
    //         $request->validate([
    //             'user_id' => 'sometimes|required|exists:users,id',
    //             // Kita tidak memperbolehkan edit buku karena akan merusak data stok
    //             // 'book_id' => 'sometimes|required|exists:books,id',
    //         ]);

    //         $data = $request->only(['user_id']);
    //         $loans->update($data);

    //         return response()->json([
    //             'message' => 'Loan data successfully updated.',
    //             'data' => $loans->load(['user', 'book']) // Muat ulang relasi
    //         ], 200);

    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Loan not found'], 404);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'An error occurred while updating.', 'error' => $e->getMessage()], 500);
    //     }
    // }

     public function return(Request $request, $id): JsonResponse
    {
        // Validasi: kita butuh status buku yang dikembalikan
        $request->validate([
            'books_status' => 'required|array',
            'books_status.*.id' => 'required|exists:books,id',
            'books_status.*.status' => 'required|in:Baik,Rusak',
        ]);

        try {
            $loan = DB::transaction(function () use ($request, $id) {
                $loan = Loan::with('books')->findOrFail($id);

                if ($loan->status_peminjaman === 'Dikembalikan') {
                    throw new \Exception('Peminjaman ini sudah dikembalikan.');
                }

                // ==========================================================
                // AWAL LOGIKA PERHITUNGAN DENDA
                // ==========================================================

                // 1. Hitung Denda Keterlambatan
                $denda_keterlambatan = 0;
                $tanggal_seharusnya_kembali = Carbon::parse($loan->tanggal_kembali);
                $tanggal_aktual_kembali = Carbon::now();

                if ($tanggal_aktual_kembali->greaterThan($tanggal_seharusnya_kembali)) {
                    $hari_terlambat = $tanggal_aktual_kembali->diffInDays($tanggal_seharusnya_kembali);
                    $tarif_harian = 1000; // Contoh: Rp 1.000 per hari
                    $denda_keterlambatan = $hari_terlambat * $tarif_harian;
                }

                // 2. Hitung Denda Kerusakan Buku
                $denda_kerusakan = 0;
                $tarif_kerusakan = 25000; // Contoh: Rp 25.000 per buku rusak
                $jumlah_buku_rusak = 0;

                foreach ($request->books_status as $bookStatus) {
                    if ($bookStatus['status'] === 'Rusak') {
                        $jumlah_buku_rusak++;
                    }
                }

                $denda_kerusakan = $jumlah_buku_rusak * $tarif_kerusakan;

                // 3. Hitung Denda Total
                $total_denda = $denda_keterlambatan + $denda_kerusakan;

                // ==========================================================
                // AKHIR LOGIKA PERHITUNGAN DENDA
                // ==========================================================

                // Update status peminjaman utama dengan total denda
                $loan->update([
                    'status_peminjaman' => 'Dikembalikan',
                    'denda' => $total_denda, // Simpan total denda di sini
                ]);

                // Kembalikan stok & update status buku di pivot
                foreach ($request->books_status as $bookStatus) {
                    $book = Book::find($bookStatus['id']);
                    if ($book) {
                        $book->increment('stock');
                    }
                    $loan->books()->updateExistingPivot($bookStatus['id'], [
                        'status_buku' => $bookStatus['status'],
                    ]);
                }
                return $loan;
            });

            $loan->refresh();

            return response()->json([
                'message' => 'Buku berhasil dikembalikan.',
                'data' => $loan->load(['user', 'books'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // public function destroy($id): JsonResponse
    // {
    //     try {
    //         $loan = Loan::findOrFail($id);
    //         $loan->delete();

    //         return response()->json(['message' => 'Loan successfully deleted.']);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Loan not found.'], 404);
    //     }
    // }

    public function destroy($id): JsonResponse
    {
       // Implementasi destroy tetap sama, tapi penggunaannya berbeda.
       // JANGAN gunakan ini untuk mengembalikan buku.
       try {
           $loan = Loan::findOrFail($id);

           // Logika tambahan: jika peminjaman yang dihapus masih berstatus 'Dipinjam',
           // kembalikan stoknya.
           if ($loan->status === 'Dipinjam') {
               $book = Book::find($loan->book_id);
               if ($book) {
                   $book->increment('stock');
               }
           }

           $loan->delete();

           return response()->json(['message' => 'Loan data successfully deleted.']);
       } catch (ModelNotFoundException $e) {
           return response()->json(['message' => 'Loan not found.'], 404);
       }
    }
}
