<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(): JsonResponse
    {
        $loans = Loan::with(['user','book'])->latest()->get();
        return response()->json($loans, 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $loan = Loan::with(['user','book'])->findOrFail($id);
            return response()->json($loan, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Loan not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
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
                $book = Book::findOrFail($request->book_id);

                // Cek apakah stok masih tersedia
                if ($book->stock < 1) {
                    throw new \Exception('Book is out of stock.');
                }

                // Kurangi stok buku
                $book->decrement('stock');

                // Buat data peminjaman dengan status default 'Dipinjam'
                return Loan::create([
                    'user_id' => $request->user_id,
                    'book_id' => $request->book_id,
                    'status' => 'Dipinjam', // Status awal
                ]);
            });

            return response()->json([
                'message' => 'Loan successfully created.',
                'data' => $loan
            ], 201);

        } catch (\Exception $e) {
            // Tangani error jika stok habis atau masalah lain
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


    public function update(Request $request, $id): JsonResponse
    {
        try {
            $loan = Loan::findOrFail($id);

            // KONDISI 1: Jika ini adalah permintaan PENGEMBALIAN BUKU
            if ($request->has('status') && $request->status === 'Dikembalikan') {
                if ($loan->status === 'Dikembalikan') {
                    return response()->json(['message' => 'This book has already been returned.'], 400);
                }

                DB::transaction(function () use ($loan) {
                    $loan->update(['status' => 'Dikembalikan']);
                    $book = Book::findOrFail($loan->book_id);
                    $book->increment('stock');
                });

                return response()->json([
                    'message' => 'Book successfully returned.',
                    'data' => $loan->load(['user', 'book']) // Muat ulang relasi
                ], 200);
            }

            // KONDISI 2: Jika ini adalah permintaan EDIT BIASA
            $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                // Kita tidak memperbolehkan edit buku karena akan merusak data stok
                // 'book_id' => 'sometimes|required|exists:books,id',
            ]);

            $data = $request->only(['user_id']);
            $loan->update($data);

            return response()->json([
                'message' => 'Loan data successfully updated.',
                'data' => $loan->load(['user', 'book']) // Muat ulang relasi
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Loan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating.', 'error' => $e->getMessage()], 500);
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
