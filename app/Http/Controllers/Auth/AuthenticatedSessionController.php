<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse; // Додали для явного зазначення типу
use Illuminate\Http\Response as BaseResponse; // Базовий клас відповіді

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): BaseResponse|JsonResponse // Змінено тип повернення
    {
        $request->authenticate();

        $request->session()->regenerate();

        // !!! ВИПРАВЛЕНО ДЛЯ SPA: Повертаємо 204 No Content замість перенаправлення !!!
        return response()->noContent();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): BaseResponse|JsonResponse // Змінено тип повернення
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // !!! ВИПРАВЛЕНО ДЛЯ SPA: Повертаємо 204 No Content замість перенаправлення !!!
        return response()->noContent();
    }
}
