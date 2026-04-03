<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    /**
     * Получить список всех действий в системе.
     * * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Мы используем paginate, но убираем strict loading для теста
            $logs = \Spatie\Activitylog\Models\Activity::latest()
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить историю изменений для конкретной модели (например, только по студенту #1).
     *
     * @param string $modelType ('student', 'user', 'group')
     * @param int $modelId
     * @return JsonResponse
     */
    public function showByModel(string $modelType, int $modelId): JsonResponse
    {
        // Карта соответствия коротких имен полным путям классов моделей
        $modelsMap = [
            'student' => \App\Models\Student::class,
            'user'    => \App\Models\User::class,
            'group'   => \App\Models\Group::class,
            'payment' => \App\Models\Payment::class,
        ];

        $className = $modelsMap[strtolower($modelType)] ?? null;

        if (!$className) {
            return response()->json([
                'success' => false,
                'message' => 'Тип модели не поддерживается'
            ], 404);
        }

        $logs = Activity::where('subject_type', $className)
            ->where('subject_id', $modelId)
            ->with('causer')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Очистка старых логов (опционально).
     */
    public function clean(): JsonResponse
    {
        // Удаляет логи старше срока, указанного в конфиге activitylog.php
        \Illuminate\Support\Facades\Artisan::call('activitylog:clean');

        return response()->json([
            'success' => true,
            'message' => 'Старые логи успешно удалены'
        ]);
    }
}
