<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Викликаємо RoleSeeder для створення всіх ролей (admin, teacher)
        // Це прибирає дублювання коду.
        $this->call([
            RoleSeeder::class,
        ]);

        // 2. Створення тестового Адміністратора
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'Головний Адміністратор',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Призначаємо роль за назвою (Spatie це підтримує автоматично)
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // 3. Створення тестового Викладача
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@crm.test'],
            [
                'name' => 'Тестовий Викладач',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Призначаємо роль за назвою
        if (!$teacher->hasRole('teacher')) {
            $teacher->assignRole('teacher');
        }
    }
}
