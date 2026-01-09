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
     * Отримати список усіх користувачів (для таблиці в адмінці)
     */
    public function index()
    {
        $users = User::with('roles')->paginate(10);

        return response()->json([
            'message' => 'Список користувачів отримано',
            'data' => $users,
        ], 200);
    }

    /**
     * Створити користувача через адмін-панель (з вибором ролі)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => ['required', Rule::in(['admin', 'teacher'])],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Оскільки в моделі спрацював 'booted' і дав 'teacher',
        // ми синхронізуємо роль на ту, яку обрав адмін.
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Користувача успішно створено адміном',
            'user'    => $user->load('roles')
        ], 201);
    }

    /**
     * Оновити дані користувача та його роль
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['sometimes', Rule::in(['admin', 'teacher'])],
        ]);

        // Оновлюємо тільки ті поля, що прийшли в запиті
        $user->update($request->only('name', 'email'));

        // Якщо адмін змінив роль
        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'Дані користувача оновлено',
            'user'    => $user->load('roles')
        ]);
    }

    /**
     * Видалити користувача
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // Захист: адмін не може видалити сам себе
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Ви не можете видалити власний акаунт'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Користувача успішно видалено'
        ]);
    }
}
