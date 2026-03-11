<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Отримати список персоналу (Адміни та Вчителі).
     */
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'groups' => function($query) {
            $query->withCount('students');
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
        $currentUser = Auth::user();

        // УМОВА: Якщо користувач, якого редагують — адмін, і це НЕ ви самі, забороняємо доступ
        if ($user->hasRole('admin') && $currentUser->id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Цей акаунт є адміністраторським. Ви не можете редагувати самостійні адмін-профілі.'
            ], 403);
        }

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'status'         => 'sometimes|string',
            'specialization' => 'sometimes|nullable|string|max:255',
            'weekly_load'    => 'sometimes|integer|min:0|max:168',
        ]);

        $user->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Дані оновлено',
            'user'    => $user->load('roles', 'groups')
        ]);
    }

    /**
     * Спеціальний метод для зміни ролі (викликається через PUT admin/users/{user}/role)
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        // Не дозволяємо змінювати роль самому собі через цей ендпоїнт (для безпеки)
        if (Auth::id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ви не можете змінити роль самому собі'
            ], 400);
        }

        // Якщо користувач вже адмін, інший адмін не може змінити його роль назад на вчителя
        // (Реалізація умови про "самостійність" адмін-акаунта)
        if ($user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ви не можете змінити роль іншому адміністратору'
            ], 403);
        }

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'teacher'])]
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'status'  => 'success',
            'message' => "Роль користувача успішно змінена на {$request->role}",
            'user'    => $user->load('roles')
        ]);
    }

    /**
     * Видалити користувача персоналу.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Ви не можете видалити власний акаунт'], 403);
        }

        // Аналогічно: забороняємо видаляти інших адмінів
        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Ви не можете видалити іншого адміністратора'], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Користувача видалено'
        ]);
    }
}
