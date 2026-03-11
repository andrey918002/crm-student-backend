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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "Таблиці очищено. Починаємо заповнення...\n";

        // Отключаем проверку внешних ключей для очистки
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Student::truncate();
        Group::truncate();
        Payment::truncate();
        Attendance::truncate();
        DB::table('roles')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Создаем роли персонала
        $adminRole = Role::create(['name' => 'admin']);
        $teacherRole = Role::create(['name' => 'teacher']);

        // 2. Создаем персонал (Админ и Учитель)
        $admin = User::create([
            'name' => 'Головний Адміністратор',
            'email' => 'admin@crm.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->assignRole($adminRole);

        $teacher = User::create([
            'name' => 'Олександр Вчитель',
            'email' => 'teacher@crm.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $teacher->assignRole($teacherRole);

        // 3. Создаем СТУДЕНТОВ (в новую таблицу students)
        $student1 = Student::create([
            'name' => 'Іван Іванов',
            'email' => 'ivan@example.com',
            'phone' => '+380990001122',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'name' => 'Марія Сидоренко',
            'email' => 'maria@example.com',
            'status' => 'active',
        ]);

        $student3 = Student::create([
            'name' => 'Петро Петренко',
            'status' => 'inactive', // Для проверки статистики
        ]);

        // 4. Создаем Группу
        $group = Group::create([
            'name' => 'English Upper-Intermediate',
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        // Привязываем студентов к группе
        $group->students()->attach([$student1->id, $student2->id, $student3->id]);

        // 5. Создаем ПЛАТЕЖИ (используем student_id!)
        Payment::create([
            'student_id' => $student1->id,
            'amount' => 1200.00,
            'paid_at' => now(),
        ]);

        Payment::create([
            'student_id' => $student2->id,
            'amount' => 1500.00,
            'paid_at' => now()->subDays(5),
        ]);

        // 6. Создаем ПОСЕЩАЕМОСТЬ (используем student_id!)
        Attendance::create([
            'student_id' => $student1->id,
            'group_id' => $group->id,
            'lesson_date' => now(),
            'is_present' => true,
        ]);

        Attendance::create([
            'student_id' => $student2->id,
            'group_id' => $group->id,
            'lesson_date' => now(),
            'is_present' => false,
        ]);

        echo "Заповнення завершено успішно!\n";
    }
}
