<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request; // Додали Request
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Teacher\GroupController;

// =========================================================================
// INERTIA/WEB ROUTES (Існуючі маршрути Breeze)
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

// Підключення маршрутів Breeze (login, register, logout)
require __DIR__.'/auth.php';


// =========================================================================
// API ROUTES FOR FRONTEND (ЗАХИЩЕНІ СЕСІЄЮ SANCTUM)
// =========================================================================

Route::prefix('api')->middleware('auth:sanctum')->group(function () {

    // 1. Маршрут для отримання даних поточного користувача (/api/user)
    Route::get('/user', function (Request $request) {
        // !!! ВИПРАВЛЕНО: Додаємо завантаження ролей (load('roles')) !!!
        return $request->user()->load('roles');
    });

    // 2. СЕКЦІЯ АДМІНІСТРАТОРА (Вкладено всередину /api та auth:sanctum)
    Route::middleware('role:admin')->group(function () {
        // Маршрут: /api/admin/users
        Route::get('admin/users', [UserController::class, 'index']);

        // ДОДАТИ: Створення, Оновлення, Видалення користувачів через ресурс
        Route::apiResource('admin/users', UserController::class)
            ->only(['store', 'update', 'destroy']);

        // Тут будуть усі інші маршрути адміністратора
        // Route::post('admin/users', [UserController::class, 'store']);
    });

    // 3. СЕКЦІЯ ВИКЛАДАЧА (Вкладено всередину /api та auth:sanctum)
    Route::middleware('role:teacher')->group(function () {
        // Маршрут: /api/teacher/groups
        Route::get('teacher/groups', [GroupController::class, 'index']);
    });
});
