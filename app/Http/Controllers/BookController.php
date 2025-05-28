<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            $book = Book::findOrFail($id);
            return response()->json($book, 200);
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
            $book = Book::findOrFail($id);

            $request->validate([
                'title' => 'sometimes|string|max:255',
                'isbn' => 'sometimes|string|unique:books,isbn,' . $book->id,
                'publisher' => 'sometimes|string|max:255',
                'year_published' => 'sometimes|string|max:4',
                'stock' => 'sometimes|integer|min:0',
            ]);

            // Only update the fields provided
            $data = $request->only(['title', 'isbn', 'publisher', 'year_published', 'stock']);
            $book->update($data);

            return response()->json([
                'message' => $book->wasChanged()
                    ? 'Book data successfully updated.'
                    : 'No changes were made.',
                'data' => $book
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();

            return response()->json(['message' => 'Book successfully deleted.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book not found.'], 404);
        }
    }
}
