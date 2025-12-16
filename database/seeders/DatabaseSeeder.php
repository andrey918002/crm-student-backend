<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role; // <-- Важливо: імпортуємо модель Role

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Створення Ролей
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);

        // 2. Створення тестового Адміністратора
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'Головний Адміністратор',
                'password' => Hash::make('password'), // Замініть надійнішим паролем для продакшну
                'email_verified_at' => now(),
            ]
        );

        // 3. Призначення ролі Адміністратору
        $admin->assignRole($adminRole);

        // 4. Створення тестового Викладача
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@crm.test'],
            [
                'name' => 'Тестовий Викладач',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 5. Призначення ролі Викладачу
        $teacher->assignRole($teacherRole);
    }
}
