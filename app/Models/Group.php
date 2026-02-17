<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group extends Model
{
    protected $fillable = [
        'name',
        'students_count',
        'max_students',
        'progress',
        'status',
        'teacher_id'
    ];

    /**
     * Зв'язок: Група належить конкретному вчителю
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class)->withPivot('grade')->withTimestamps();
    }
}
