<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\ProfileController; // Стандартный для Inertia
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

// Импортируем API контроллеры
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Teacher\GroupController;
// Добавляем импорт контроллера логов
use App\Http\Controllers\Api\Admin\ActivityLogController;

// =========================================================================
// INERTIA/WEB ROUTES (Для стандартного отображения страниц)
// =========================================================================

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// =========================================================================
// API ROUTES FOR FRONTEND (Работа с React/Axios через JSON)
// =========================================================================

Route::prefix('api')->middleware('auth:sanctum')->group(function () {

    // 1. Текущий пользователь
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });

    // 2. Личный профиль
    Route::get('/profile/data', [ApiProfileController::class, 'show']);
    Route::put('/profile/data', [ApiProfileController::class, 'update']);

    // 3. Секция администратора
    Route::middleware('role:admin')->group(function () {
        // Управление персоналом
        Route::apiResource('admin/users', UserController::class);
        Route::put('admin/users/{user}/role', [UserController::class, 'updateRole']);
        Route::get('/admin/stats', [DashboardController::class, 'getStats']);

        // --- ЛОГИ АКТИВНОСТИ ---
        // Получить все логи (с пагинацией)
        Route::get('admin/logs', [ActivityLogController::class, 'index']);

        // Получить логи конкретной модели (студент, группа и т.д.)
        // Пример: /api/admin/logs/student/1
        Route::get('admin/logs/{type}/{id}', [ActivityLogController::class, 'showByModel']);

        // Очистка логов (согласно конфигу)
        Route::post('admin/logs/clean', [ActivityLogController::class, 'clean']);
    });

    // 4. Секция преподавателя
    Route::middleware('role:teacher')->group(function () {
        Route::get('teacher/groups', [GroupController::class, 'index']);
        Route::patch('teacher/groups/{group}/students/{student}/grade', [GroupController::class, 'updateGrade']);
    });
});
