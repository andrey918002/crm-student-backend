<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'max_students',
        'progress',
        'status',
        'teacher_id'
        // 'students_count' удаляем из fillable, так как будем считать его динамически
    ];

    /**
     * Динамически добавляем поле students_count в JSON ответ
     */
    protected $appends = ['students_count'];

    /**
     * Связь: Группа принадлежит учителю
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Связь: Студенты в группе (используем новую модель Student)
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'group_student')
            ->withPivot('grade')
            ->withTimestamps();
    }

    /**
     * Геттер для поля students_count
     * Теперь фронтенд всегда будет видеть актуальное число
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }
}
