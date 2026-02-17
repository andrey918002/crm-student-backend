<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Отримати список усіх користувачів.
     * Додано: підвантаження ролей ТА груп для вчителів.
     */
    public function index()
    {
        // Додаємо 'groups', щоб фронтенд міг показати кількість груп у кожного вчителя
        $users = User::with(['roles', 'groups'])->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Список користувачів отримано',
            'data' => $users,
        ], 200);
    }

    /**
     * Створити користувача через адмін-панель.
     * Додано: підтримка спеціалізації та навантаження.
     */
    public function store(Request $request)
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
            'specialization' => $validated['specialization'] ?? null,
            'weekly_load'    => $validated['weekly_load'] ?? 0,
        ]);

        // Синхронізуємо обрану роль
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Користувача успішно створено адміном',
            'user'    => $user->load('roles', 'groups')
        ], 201);
    }

    /**
     * Оновити дані користувача.
     * Додано: оновлення нових полів.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'           => ['sometimes', Rule::in(['admin', 'teacher'])],
            'specialization' => 'sometimes|nullable|string|max:255',
            'weekly_load'    => 'sometimes|integer|min:0|max:168',
        ]);

        // Оновлюємо тільки ті поля, що пройшли валідацію
        $user->update($validated);

        // Якщо в запиті була зміна ролі
        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'Дані користувача оновлено',
            'user'    => $user->load('roles', 'groups')
        ]);
    }

    /**
     * Видалити користувача.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // Захист від самовидалення
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Ви не можете видалити власний акаунт'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Користувача успішно видалено'
        ]);
    }
}
