<?php

namespace App\Http\Controllers;

use App\Models\BookAuthor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookAuthorController extends Controller
{
    public function index(): JsonResponse
    {
        $bookAuthors = BookAuthor::all();
        return response()->json($bookAuthors, 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $bookAuthor = BookAuthor::findOrFail($id);
            return response()->json($bookAuthor, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book Author not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'author_id' => 'required|exists:authors,id',
        ]);

        $bookAuthor = BookAuthor::create([
            'book_id' => $request->book_id,
            'author_id' => $request->author_id,
        ]);

        return response()->json([
            'message' => 'Book Author successfully created.',
            'data' => $bookAuthor
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $bookAuthor = BookAuthor::findOrFail($id);

            $request->validate([
                'book_id' => 'sometimes|exists:books,id',
                'author_id' => 'sometimes|exists:authors,id',
            ]);

            // Only update the fields provided
            $data = $request->only(['book_id', 'author_id']);
            $bookAuthor->update($data);

            return response()->json([
                'message' => $bookAuthor->wasChanged()
                    ? 'Book Author data successfully updated.'
                    : 'No changes were made.',
                'data' => $bookAuthor
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book Author not found'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $bookAuthor = BookAuthor::findOrFail($id);
            $bookAuthor->delete();

            return response()->json(['message' => 'Book Author successfully deleted.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Book Author not found.'], 404);
        }
    }
}
