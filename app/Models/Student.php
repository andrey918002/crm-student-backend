<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    /**
     * Поля для массового заполнения.
     * Теперь мы используем 'name', 'status' для дашборда и контактные данные.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',           // 'active' (оплачено) или 'inactive'
        'additional_info'   // Любые заметки об ученике
    ];

    /**
     * Группы, в которых учится студент.
     * Один студент может быть в нескольких группах (Many-to-Many).
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('grade')
            ->withTimestamps();
    }

    /**
     * Все платежи этого студента.
     * Используется для карточки "Прибуток" на дашборде.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * История посещаемости студента.
     * Используется для расчета % посещаемости на дашборде.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Проверка, активен ли студент.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
