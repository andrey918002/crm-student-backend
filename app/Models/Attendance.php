<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'student_id', // Важно: теперь привязка к студенту
        'group_id',
        'lesson_date',
        'is_present',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'is_present' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
