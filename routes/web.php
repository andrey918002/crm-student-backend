<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;

// Контроллеры веб-части
use App\Http\Controllers\ProfileController;

// API Контроллеры
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\Teacher\GroupController;

// =========================================================================
// INERTIA/WEB ROUTES (Стандартные страницы)
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
// API ROUTES FOR FRONTEND (React / Axios)
// =========================================================================

Route::prefix('api')->middleware('auth:sanctum')->group(function () {

    // 1. Текущий пользователь
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('roles'),
        ]);
    });

    // 2. Личный профиль
    Route::get('/profile/data', [ApiProfileController::class, 'show']);
    Route::put('/profile/data', [ApiProfileController::class, 'update']);

    // --- ОБЩИЙ РЕСУРС (Доступен и Админам, и Учителям) ---
    // Используем нейтральный префикс или просто students.
    // Оставляю 'admin/students' для совместимости с текущей логикой структуры URL,
    // но выношу за пределы middleware('role:admin')
    Route::middleware(['role:admin|teacher'])->group(function () {
        Route::apiResource('students', StudentController::class);
    });

    // 3. Секция ТОЛЬКО Администратора
    Route::middleware('role:admin')->group(function () {
        // Управление персоналом (Админы/Учителя)
        Route::apiResource('admin/users', UserController::class);
        Route::put('admin/users/{user}/role', [UserController::class, 'updateRole']);
        Route::get('/admin/stats', [DashboardController::class, 'getStats']);

        // Логи активности
        Route::get('admin/logs', [ActivityLogController::class, 'index']);
        Route::get('admin/logs/{type}/{id}', [ActivityLogController::class, 'showByModel']);
        Route::post('admin/logs/clean', [ActivityLogController::class, 'clean']);
    });

    // 4. Секция ТОЛЬКО Преподавателя
    Route::middleware('role:teacher')->group(function () {
        Route::get('teacher/groups', [GroupController::class, 'index']);
        Route::get('teacher/groups/{group}', [GroupController::class, 'show']);
        Route::patch('teacher/groups/{group}', [GroupController::class, 'update']);
        Route::patch('teacher/groups/{group}/students/{student}/grade', [GroupController::class, 'updateGrade']);
    });
});
