<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Imports\BookImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class BookController extends Controller
{
    public function index(): JsonResponse
    {
        $books = Book::all();
        return response()->json($books, 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $books = Book::findOrFail($id);
            return response()->json($books, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn',
            'publisher' => 'required|string|max:255',
            'year_published' => 'required|string|max:4',
            'stock' => 'required|integer|min:0',
        ]);

        $book = Book::create([
            'title' => $request->title,
            'isbn' => $request->isbn,
            'publisher' => $request->publisher,
            'year_published' => $request->year_published,
            'stock' => $request->stock,
        ]);

        return response()->json([
            'message' => 'Book successfully created.',
            'data' => $book
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $books = Book::findOrFail($id);

            $request->validate([
                'title' => 'sometimes|string|max:255',
                'isbn' => 'sometimes|string|unique:books,isbn,' . $books->id,
                'publisher' => 'sometimes|string|max:255',
                'year_published' => 'sometimes|string|max:4',
                'stock' => 'sometimes|integer|min:0',
            ]);

            // Only update the fields provided
            $data = $request->only(['title', 'isbn', 'publisher', 'year_published', 'stock']);
            $books->update($data);

            return response()->json([
                'message' => $books->wasChanged()
                    ? 'Book data successfully updated.'
                    : 'No changes were made.',
                'data' => $books
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $books = Book::findOrFail($id);
            $books->delete();

            return response()->json(['message' => 'Book successfully deleted.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book not found.'], 404);
        }
    }

    public function import(Request $request)
    {
        // Validasi awal untuk memastikan file ada dan merupakan file excel/csv
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        // <-- 2. Blok Pengecekan Header dimulai -->
        $file = $request->file('file');

        // Ambil hanya baris header dari file
        $headings = (new HeadingRowImport)->toArray($file);

        // Definisikan header yang wajib ada
        $requiredHeadings = ['judul', 'isbn', 'penerbit', 'tahun_terbit', 'stok'];

        // Cek apakah header dari file (headings[0][0]) sudah mencakup semua header wajib
        // array_diff akan mencari nilai di $requiredHeadings yang TIDAK ADA di $headings[0][0]
        $missingHeadings = array_diff($requiredHeadings, $headings[0][0]);

        if (!empty($missingHeadings)) {
            // Jika ada header yang hilang, kirim response error yang spesifik
            return response()->json([
                'message' => 'Format kolom Excel tidak sesuai. Pastikan terdapat kolom: ' . implode(', ', $requiredHeadings),
                'missing' => $missingHeadings // Opsional: kirim info kolom apa yang hilang
            ], 422); // 422 adalah kode status yang tepat untuk error validasi
        }
        // <-- Akhir Blok Pengecekan Header -->

        try {
            // Jika header sudah benar, baru lakukan impor
            Excel::import(new BookImport, $file);

            return response()->json([
                'message' => 'Data buku berhasil diimpor!'
            ], 200);

        } catch (\Exception $e) {
            // Catch untuk error tak terduga lainnya saat proses impor
            return response()->json([
                'message' => 'Terjadi kesalahan server saat memproses file.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
