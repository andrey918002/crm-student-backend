<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Отримати список персоналу (Адміни та Вчителі).
     */
    public function index(): JsonResponse
    {
        // Завантажуємо тільки персонал. Оскільки в таблиці users тепер лише вони,
        // ми просто беремо всіх користувачів з їхніми ролями та групами, якими вони керують.
        $users = User::with(['roles', 'groups' => function($query) {
            $query->withCount('students'); // Це важливо, щоб фронтенд бачив кількість учнів у вчителя
        }])->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Список персоналу отримано',
            'data'    => $users,
        ], 200);
    }

    /**
     * Створити адміністратора або вчителя.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users',
            'password'       => 'required|string|min:8',
            // Тепер дозволені тільки ролі персоналу
            'role'           => ['required', Rule::in(['admin', 'teacher'])],
            'specialization' => 'nullable|string|max:255',
            'weekly_load'    => 'nullable|integer|min:0|max:168',
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => Hash::make($validated['password']),
            'status'         => 'active',
            'specialization' => $validated['specialization'] ?? null,
            'weekly_load'    => $validated['weekly_load'] ?? 0,
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Користувача персоналу успішно створено',
            'user'    => $user->load('roles', 'groups')
        ], 201);
    }

    /**
     * Оновити дані вчителя або адміна.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'           => ['sometimes', Rule::in(['admin', 'teacher'])],
            'status'         => 'sometimes|string',
            'specialization' => 'sometimes|nullable|string|max:255',
            'weekly_load'    => 'sometimes|integer|min:0|max:168',
        ]);

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->update($request->except('role'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Дані оновлено',
            'user'    => $user->load('roles', 'groups')
        ]);
    }

    /**
     * Видалити користувача персоналу.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Ви не можете видалити власний акаунт'], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Користувача видалено'
        ]);
    }
}
