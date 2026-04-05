<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Отримати список груп поточного викладача з учнями.
     */
    public function index(Request $request)
    {
        // Додаємо with('students'), щоб підтягнути список учнів для кожної групи
        $groups = $request->user()->groups()
            ->with('students')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Список ваших груп та студентів отримано',
            'data' => $groups
        ], 200);
    }

    /**
     * Отримати детальну інформацію про конкретну групу.
     */
    public function show(Request $request, string $id)
    {
        // Тут також додаємо завантаження студентів
        $group = $request->user()->groups()
            ->with('students')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $group
        ], 200);
    }

    /**
     * Оновити прогрес або статус групи.
     */
    public function update(Request $request, string $id)
    {
        $group = $request->user()->groups()->findOrFail($id);

        $validated = $request->validate([
            'progress' => 'sometimes|integer|min:0|max:100',
            'status' => 'sometimes|string|in:pending,active,finished',
        ]);

        $group->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Дані групи оновлено',
            'data' => $group
        ]);
    }
    /**
     * Оновити оцінку студента в групі.
     */
    public function updateGrade(Request $request, $groupId, $studentId)
    {
        $request->validate([
            'grade' => 'required|integer|min:0|max:100',
        ]);

        // Знаходимо групу цього вчителя
        $group = $request->user()->groups()->findOrFail($groupId);

        // Оновлюємо оцінку в проміжній таблиці (pivot)
        $group->students()->updateExistingPivot($studentId, [
            'grade' => $request->grade
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'group_id' => (int) $groupId,
                'student_id' => (int) $studentId,
                'grade' => (int) $request->grade,
            ],
            'message' => 'Оцінку студента оновлено',
        ]);
    }
}
