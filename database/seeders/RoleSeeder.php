<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // Важливо імпортувати саме цей клас

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Створюємо ролі, якщо їх ще немає
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'admin']);
    }
}
