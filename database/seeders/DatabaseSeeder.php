<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "Запуск глибокої симуляції даних для аналітики...\n";

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Student::truncate();
        Group::truncate();
        Payment::truncate();
        Attendance::truncate();
        DB::table('roles')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('group_student')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Створення ролей
        $adminRole = Role::create(['name' => 'admin']);
        $teacherRole = Role::create(['name' => 'teacher']);

        // 2. Створення адміна та вчителів
        $admin = User::create([
            'name' => 'Головний Адміністратор',
            'email' => 'admin@crm.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->assignRole($adminRole);

        $teachers = [
            ['name' => 'Олександр Вчитель', 'email' => 'teacher1@crm.test'],
            ['name' => 'Тетяна Морозова', 'email' => 'teacher2@crm.test'],
        ];

        $teacherIds = [];
        foreach ($teachers as $t) {
            $user = User::create([
                'name' => $t['name'],
                'email' => $t['email'],
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $user->assignRole($teacherRole);
            $teacherIds[] = $user->id;
        }

        // 3. Створення груп з різними датами (для графіка Груп)
        $groupDefinitions = [
            ['name' => 'English A1', 'months_ago' => 5],
            ['name' => 'English B2', 'months_ago' => 3],
            ['name' => 'English C1', 'months_ago' => 1],
        ];

        $groups = [];
        foreach ($groupDefinitions as $index => $g) {
            $createdAt = Carbon::now()->subMonths($g['months_ago'])->startOfMonth();
            $groups[] = Group::create([
                'name' => $g['name'],
                'teacher_id' => $teacherIds[$index % count($teacherIds)],
                'status' => 'active',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // 4. Створення пулу студентів
        $studentsPool = [
            ['name' => 'Іван Іванов', 'email' => 'ivan@example.com'],
            ['name' => 'Марія Сидоренко', 'email' => 'maria@example.com'],
            ['name' => 'Петро Петренко', 'email' => 'petro@example.com'],
            ['name' => 'Анна Коваль', 'email' => 'anna@example.com'],
            ['name' => 'Дмитро Бондаренко', 'email' => 'dmitro@example.com'],
            ['name' => 'Олена Кравченко', 'email' => 'olena@example.com'],
            ['name' => 'Сергій Мельник', 'email' => 'serg@example.com'],
            ['name' => 'Юлія Шевченко', 'email' => 'yulia@example.com'],
            ['name' => 'Максим Орлов', 'email' => 'max@example.com'],
            ['name' => 'Вікторія Ткач', 'email' => 'vikt@example.com'],
        ];

        foreach ($studentsPool as $index => $data) {
            $studentCreated = Carbon::now()->subMonths(5 - ($index % 6))->subDays(rand(1, 15));

            $student = Student::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => 'active',
                'created_at' => $studentCreated,
                'updated_at' => $studentCreated,
            ]);

            $availableGroups = array_filter($groups, function($g) use ($studentCreated) {
                return $g->created_at <= $studentCreated;
            });

            if (empty($availableGroups)) $availableGroups = [$groups[0]];
            $assignedGroup = $availableGroups[array_rand($availableGroups)];
            $assignedGroup->students()->attach($student->id);

            // 5. ГЕНЕРАЦІЯ ПЛАТЕЖІВ
            $paymentDate = $studentCreated->copy()->startOfMonth();
            while ($paymentDate <= Carbon::now()) {
                Payment::create([
                    'student_id' => $student->id,
                    'amount' => rand(1500, 3000),
                    'paid_at' => $paymentDate->copy()->addDays(rand(1, 10)),
                    'created_at' => $paymentDate->copy()->addDays(rand(1, 10)),
                ]);
                $paymentDate->addMonth();
            }

            // 6. ГЕНЕРАЦІЯ ВІДВІДУВАНОСТІ З РІЗНИМИ ЦИФРАМИ
            $attendanceDate = $studentCreated->copy();

            // Створюємо масив "настрою" для кожного місяця (ймовірність присутності)
            $monthlyLuck = [];
            for ($m = 0; $m <= 6; $m++) {
                $monthlyLuck[Carbon::now()->subMonths($m)->month] = rand(5, 9); // від 50% до 90%
            }

            while ($attendanceDate <= Carbon::now()) {
                if (in_array($attendanceDate->dayOfWeek, [1, 3, 5])) {
                    $currentMonth = $attendanceDate->month;
                    $luckThreshold = $monthlyLuck[$currentMonth] ?? 7;

                    Attendance::create([
                        'student_id' => $student->id,
                        'group_id' => $assignedGroup->id,
                        'lesson_date' => $attendanceDate->toDateString(),
                        // Використовуємо поріг місяця для рандому
                        'is_present' => (rand(1, 10) <= $luckThreshold),
                        'created_at' => $attendanceDate,
                    ]);
                }
                $attendanceDate->addDay();
            }
        }

        echo "Глибоке заповнення завершено. Тепер всі графіки відображатимуть динаміку за півроку!\n";
    }
}
