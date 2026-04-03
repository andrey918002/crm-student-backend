<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(): JsonResponse
    {
        try {
            // Устанавливаем локализацию для корректных названий месяцев
            Carbon::setLocale('uk');

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            // Основные счетчики
            $totalStudents = Student::count();
            $activeStudents = Student::where('status', 'active')->count();

            // ВНИМАНИЕ: Исправлено согласно вашей миграции enum('Набір', 'Активна', 'Завершена')
            $activeGroups = Group::where('status', 'Активна')->count();

            // Расчет тренда студентов
            $newStudentsThisMonth = Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $newStudentsLastMonth = Student::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
            $studentTrendUp = $newStudentsThisMonth >= $newStudentsLastMonth;

            // Финансы
            $monthlyIncome = Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])->sum('amount');
            $lastMonthIncome = Payment::whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])->sum('amount');

            $incomeTrend = 0;
            if ($lastMonthIncome > 0) {
                $incomeTrend = (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100;
            } elseif ($monthlyIncome > 0) {
                $incomeTrend = 100;
            }

            // Посещаемость за текущий месяц (среднее значение 0..1 превращаем в %)
            $avg = Attendance::whereBetween('lesson_date', [$startOfMonth, $endOfMonth])->avg('is_present');
            $attendanceRate = $avg !== null ? round($avg * 100) : 0;

            // Сбор данных для графиков
            $chartData = $this->getChartData();

            // Топ преподавателей (используем ваши новые поля specialization и weekly_load)
            $topTeachers = User::whereNotNull('specialization')
                ->orderBy('weekly_load', 'desc')
                ->take(3)
                ->get(['name', 'specialization', 'weekly_load']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'main_stats' => [
                        [
                            'label' => 'Студенти',
                            'value' => (int)$totalStudents,
                            'trend' => "+$newStudentsThisMonth за місяць",
                            'trendUp' => $studentTrendUp,
                            'icon' => 'users'
                        ],
                        [
                            'label' => 'Дохід (міс.)',
                            'value' => number_format($monthlyIncome, 0, '.', ' ') . ' ₴',
                            'trend' => round($incomeTrend, 1) . '%',
                            'trendUp' => $incomeTrend >= 0,
                            'icon' => 'currency-dollar'
                        ],
                        [
                            'label' => 'Відвідуваність',
                            'value' => $attendanceRate . '%',
                            'trend' => 'Середня за місяць',
                            'trendUp' => $attendanceRate > 75,
                            'icon' => 'calendar'
                        ],
                        [
                            'label' => 'Активні групи',
                            'value' => (int)$activeGroups,
                            'trend' => 'У процесі навчання',
                            'trendUp' => true,
                            'icon' => 'academic-cap'
                        ]
                    ],
                    'charts' => $chartData,
                    'top_teachers' => $topTeachers,
                    'recent_activity' => [
                        'new_students' => $newStudentsThisMonth,
                        'active_now' => $activeStudents
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Dashboard Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Помилка завантаження даних дашборду: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getChartData(): array
    {
        $data = [];
        // Берем последние 6 месяцев
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // 1. Доход
            $income = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');

            // 2. Студенты (кумулятивно: сколько всего было на тот момент)
            $studentsCount = Student::where('created_at', '<=', $monthEnd)->count();

            // 3. Посещаемость
            $avgAttendance = Attendance::whereBetween('lesson_date', [$monthStart, $monthEnd])
                ->avg('is_present');

            $data[] = [
                'name' => $month->translatedFormat('M'), // "Бер", "Кві" и т.д.
                'income' => (int)$income,
                'students' => (int)$studentsCount,
                'attendance' => $avgAttendance !== null ? round($avgAttendance * 100) : 0
            ];
        }
        return $data;
    }
}
