<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Imports\AuthorImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class AuthorController extends Controller
{
    public function index(): JsonResponse
    {
        $authors = Author::all();
        return response()->json($authors, 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $author = Author::findOrFail($id);
            return response()->json($author, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Author not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'birthdate' => 'required|string|max:255',
        ]);

        $author = Author::create([
            'name' => $request->name,
            'nationality' => $request->nationality,
            'birthdate' => $request->birthdate,
        ]);

        return response()->json([
            'message' => 'Author successfully created.',
            'data' => $author
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $author = Author::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'nationality' => 'sometimes|string|max:255',
                'birthdate' => 'sometimes|string|max:255',
            ]);

            // Only update the fields provided
            $data = $request->only(['name', 'nationality', 'birthdate']);
            $author->update($data);

            return response()->json([
                'message' => $author->wasChanged()
                    ? 'Author data successfully updated.'
                    : 'No changes were made.',
                'data' => $author
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Author not found'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $author = Author::findOrFail($id);
            $author->delete();

            return response()->json(['message' => 'Author successfully deleted.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Author not found.'], 404);
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
        $requiredHeadings = ['name', 'nationality', 'birthdate'];

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
            Excel::import(new AuthorImport, $file);

            return response()->json([
                'message' => 'Data penulis berhasil diimpor!'
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
