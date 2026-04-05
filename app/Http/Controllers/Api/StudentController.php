<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * Получить список студентов.
     * Админ видит всех, учитель — только тех, кто в его группах.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Начинаем запрос с подгрузкой групп
        $query = Student::with(['groups' => function ($q) {
            $q->select([
                'groups.id',
                'groups.name',
                'groups.teacher_id',
                'groups.status',
                'groups.max_students',
                'groups.progress',
            ])->withCount('students');
        }]);

        /**
         * Логика доступа:
         * Если пользователь НЕ админ, но учитель — фильтруем по группам.
         * (Предполагается использование Spatie Permission или аналогичного метода hasRole)
         */
        if ($user->hasRole('teacher') && !$user->hasRole('admin')) {
            $query->whereHas('groups', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        $students = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }

    /**
     * Создание нового студента.
     */
    public function store(Request $request): JsonResponse
    {
        // Только админ обычно может создавать студентов в CRM
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'У вас нет прав для создания студентов',
            ], 403);
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|unique:students,email',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,trial,inactive',
            'additional_info' => 'nullable|string',
        ]);

        $student = Student::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Студент успешно создан',
            'data' => $student
        ], 201);
    }

    /**
     * Просмотр конкретного студента.
     */
    public function show(Student $student): JsonResponse
    {
        $user = Auth::user();

        // Проверка: если учитель пытается посмотреть "чужого" студента
        if ($user->hasRole('teacher') && !$user->hasRole('admin')) {
            $isOwnStudent = $student->groups()->where('teacher_id', $user->id)->exists();
            if (!$isOwnStudent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Доступ запрещен',
                ], 403);
            }
        }

        $student->load(['groups', 'payments', 'attendances']);

        return response()->json([
            'status' => 'success',
            'data' => $student
        ]);
    }

    /**
     * Обновление данных студента.
     */
    public function update(Request $request, Student $student): JsonResponse
    {
        // Обычно только админ редактирует личные данные и статус
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|unique:students,email,' . $student->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|required|string|in:active,trial,inactive',
            'additional_info' => 'nullable|string',
        ]);

        $student->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Данные студента обновлены',
            'data' => $student
        ]);
    }

    /**
     * Удаление студента.
     */
    public function destroy(Student $student): JsonResponse
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Только администратор может удалять записи',
            ], 403);
        }

        $student->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Студент удален',
        ]);
    }
}
