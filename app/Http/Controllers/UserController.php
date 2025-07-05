<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(): JsonResponse
    {

        $dataUser = User::all();
        return response()->json($dataUser, 200);
    }
    // Menampilkan user berdasarkan ID
    public function show($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            return response()->json($user, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    // Menambahkan user baru
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'Akun pengguna berhasil ditambahkan.',
            'data' => $user
        ], 201);
    }

    // Mengupdate data user
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
            ]);

            // Hanya update field yang dikirim
            $data = $request->only(['name', 'email', 'password']);
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            logger('Data yg dikirim', $data);
            $user->update($data);


            return response()->json([
                'message' => $user->wasChanged()
                    ? 'Akun pengguna berhasil diupdate.'
                    : 'Tidak ada perubahan pada data pengguna.',
                'data' => $user
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        $dataToUpdate = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $dataToUpdate['password'] = bcrypt($request->password);
        }

        $user->update($dataToUpdate);

        return response()->json([
            'message' => 'Profil berhasil diupdate.',
            'data' => $user
        ], 200);
    }

    public function deleteAccount(User $user): JsonResponse
    {
        try {
            // Karena kita sudah mendapatkan $user dari rute,
            // kita bisa langsung menghapusnya.
            $user->delete();

            return response()->json(['message' => 'User berhasil dihapus.']);
        } catch (\Exception $e) {
            // Penanganan error umum jika terjadi masalah saat menghapus
            return response()->json(['message' => 'Gagal menghapus user.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json(['message' => 'User berhasil dihapus.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }
    }
}
