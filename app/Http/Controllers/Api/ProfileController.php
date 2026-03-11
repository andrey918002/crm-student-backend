<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Отримати дані профілю поточного користувача.
     * Використовується для заповнення форми "Мій профіль" у React.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'user'   => $request->user()->load('roles')
        ]);
    }

    /**
     * Оновити дані власного профілю (ім'я, email, пароль).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            // Пароль необов'язковий (якщо не заповнений — не змінюється)
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Ваш профіль успішно оновлено',
            'user'    => $user->load('roles')
        ]);
    }
}
