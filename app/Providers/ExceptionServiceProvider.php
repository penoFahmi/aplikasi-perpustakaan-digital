<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Handle unauthenticated (401)
        app(ExceptionHandler::class)->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Unauthenticated - Login diperlukan.'
            ], 401);
        });

        // Handle unauthorized (403)
        app(ExceptionHandler::class)->renderable(function (AuthorizationException $e, $request) {
            return response()->json([
                'message' => 'Forbidden - Anda tidak punya akses.'
            ], 403);
        });

        // Handle validation error (422)
        app(ExceptionHandler::class)->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        });

        // Optional: custom 500 internal server error
        app(ExceptionHandler::class)->renderable(function (Throwable $e, $request) {
            if (app()->isProduction()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan internal.',
                ], 500);
            }
        });
    }
}
