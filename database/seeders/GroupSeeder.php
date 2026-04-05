<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Database\Factories\GroupFactory;
use Illuminate\Database\Seeder;

/**
 * Додаткові тестові групи (латинські статуси).
 * Запуск: php artisan db:seed --class=GroupSeeder
 * Потрібен хоча б один користувач з роллю teacher (наприклад після RoleSeeder + створення user).
 */
class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $teacherId = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->value('id');

        if ($teacherId === null) {
            return;
        }

        foreach (GroupFactory::STATUSES as $status) {
            Group::factory()
                ->state(['teacher_id' => $teacherId, 'status' => $status])
                ->create();
        }
    }
}
