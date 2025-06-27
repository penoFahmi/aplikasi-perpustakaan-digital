<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use App\Http\Requests\RegisterRequest;
use Carbon\Carbon;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $userRole = Role::where('name', 'user')->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $userRole->id,
            'membership_date' => Carbon::now(),
        ]);

        $user->load('role');

        return (new UserResource($user))
                ->response()
                ->setStatusCode(201); // 201 Created
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();


        $user = User::whereRaw('BINARY email = ?', [$credentials['email']])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $user->load('role');


        return response()->json([
            'token' => $user->createToken('mobile-token')->plainTextToken,
            'user' => new UserResource($user)
        ]);
    }

    public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logout berhasil.',
    ]);
}
}
