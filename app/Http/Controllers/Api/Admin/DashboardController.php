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
            Carbon::setLocale('uk');

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            // Основные счетчики
            $totalStudents = Student::count();
            $activeStudents = Student::where('status', 'active')->count();
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

            // Посещаемость
            $avg = Attendance::whereBetween('lesson_date', [$startOfMonth, $endOfMonth])->avg('is_present');
            $attendanceRate = $avg !== null ? round($avg * 100) : 0;

            // Сбор данных для графиков
            $chartData = $this->getChartData();

            $topTeachers = User::whereNotNull('specialization')
                ->orderBy('weekly_load', 'desc')
                ->take(3)
                ->get(['name', 'specialization', 'weekly_load']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    // Для компонента AdminStats (4 карточки с иконками)
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
                    // ДЛЯ ВЕРХНИХ КАРТОЧЕК В Admin.tsx (чтобы не было нулей)
                    'teacher_stats' => [
                        'total_students' => $totalStudents,
                        'active_students' => $newStudentsThisMonth, // сколько пришло
                        'active_groups' => $activeGroups,
                        'total_income' => $monthlyIncome,
                        'income_trend' => round($incomeTrend, 1),
                        'attendance_rate' => $attendanceRate
                    ],
                    // ДЛЯ ГРАФИКОВ (обязательно chartData)
                    'chartData' => $chartData,
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
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $income = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');
            $studentsCount = Student::where('created_at', '<=', $monthEnd)->count();
            $avgAttendance = Attendance::whereBetween('lesson_date', [$monthStart, $monthEnd])
                ->avg('is_present');

            $data[] = [
                'name' => $month->translatedFormat('M'),
                'income' => (int)$income,
                'revenue' => (int)$income, // дублируем для фронта
                'students' => (int)$studentsCount,
                'attendance' => $avgAttendance !== null ? round($avgAttendance * 100) : 0
            ];
        }
        return $data;
    }
}
