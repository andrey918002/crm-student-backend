<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            $totalStudents = Student::count();
            $activeStudents = Student::where('status', 'active')->count();
            $activeGroups = Group::where('status', 'active')->count();

            $newStudentsThisMonth = Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $newStudentsLastMonth = Student::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
            $studentTrendUp = $newStudentsThisMonth >= $newStudentsLastMonth;

            $monthlyIncome = Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])->sum('amount');
            $lastMonthIncome = Payment::whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])->sum('amount');

            $incomeTrend = 0;
            if ($lastMonthIncome > 0) {
                $incomeTrend = (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100;
            } elseif ($monthlyIncome > 0) {
                $incomeTrend = 100;
            }

            // Посещаемость за текущий месяц
            $avg = Attendance::whereBetween('lesson_date', [$startOfMonth, $endOfMonth])->avg('is_present');
            $attendanceRate = $avg !== null ? round($avg * 100) : 0;

            $chartData = $this->getChartData();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'teacher_stats' => [
                        'total_students' => (int)$totalStudents,
                        'active_students' => (int)$activeStudents,
                        'active_groups' => (int)$activeGroups,
                        'total_income' => (float)$monthlyIncome,
                        'attendance_rate' => (int)$attendanceRate,
                        'income_trend' => round($incomeTrend, 1)
                    ],
                    'crm_stats' => [
                        'total_students' => (int)$totalStudents,
                        'active_groups' => (int)$activeGroups,
                        'new_this_month' => $newStudentsThisMonth
                    ],
                    'main_stats' => [
                        [
                            'label' => 'Студенти',
                            'value' => (int)$totalStudents,
                            'trend' => "+$newStudentsThisMonth за місяць",
                            'trendUp' => $studentTrendUp
                        ],
                        [
                            'label' => 'Дохід (міс.)',
                            'value' => number_format($monthlyIncome, 0, '.', ' ') . ' ₴',
                            'trend' => round($incomeTrend, 1) . '%',
                            'trendUp' => $incomeTrend >= 0
                        ],
                    ],
                    'charts' => $chartData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Dashboard Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
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

            // 2. Студенты (общее кол-во на тот момент)
            $studentsCount = Student::where('created_at', '<=', $monthEnd)->count();

            // 3. Группы (кол-во созданных до конца того месяца)
            $groupsCount = Group::where('created_at', '<=', $monthEnd)->count();

            // 4. Посещаемость (среднее за конкретный месяц по колонке lesson_date)
            $avgAttendance = Attendance::whereBetween('lesson_date', [$monthStart, $monthEnd])
                ->avg('is_present');

            $data[] = [
                'name' => $month->translatedFormat('M'), // Напр: "Березень"
                'income' => (int)$income,
                'students' => (int)$studentsCount,
                'groups' => (int)$groupsCount,
                'attendance' => $avgAttendance !== null ? round($avgAttendance * 100) : 0
            ];
        }
        return $data;
    }
}
