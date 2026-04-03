<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Group extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'max_students',
        'progress',
        'status',
        'teacher_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('groups')
            ->setDescriptionForEvent(fn(string $eventName) => "Группа '{$this->name}' была {$eventName}");
    }

    protected $appends = ['students_count'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'group_student')
            ->withPivot('grade')
            ->withTimestamps();
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }
}
