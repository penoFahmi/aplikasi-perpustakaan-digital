<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
}
