<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        // 1. Рахуємо дані
        $usersCount = User::count();

        // Активні сесії (токені Sanctum, використані за останні 15 хв)
        $activeSessions = DB::table('personal_access_tokens')
            ->where('last_used_at', '>', now()->subMinutes(15))
            ->count();

        $totalStudents = Group::sum('students_count');
        $activeGroups = Group::where('status', 'Активна')->count();

        // 2. Формуємо відповідь точно під дизайн фронтенду
        return response()->json([
            'status' => 'success',
            'data' => [
                // Ці дані підуть у компоненти AdminStats (три верхні картки)
                'main_stats' => [
                    [
                        'label' => 'Користувачі',
                        'value' => number_format($usersCount),
                        'trend' => '+12%', // Можна буде потім вирахувати реально
                        'trendUp' => true
                    ],
                    [
                        'label' => 'Активні сесії',
                        'value' => $activeSessions,
                        'trend' => 'Live',
                        'trendUp' => true
                    ],
                    [
                        'label' => 'Завантаження CPU',
                        'value' => '18%',
                        'trend' => '-2%',
                        'trendUp' => false
                    ],
                ],
                // Ці дані знадобляться для карток у розділі "Викладачі"
                'teacher_stats' => [
                    'total_students' => $totalStudents,
                    'active_groups' => $activeGroups,
                ]
            ]
        ]);
    }
}
