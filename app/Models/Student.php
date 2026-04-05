<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Student extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'additional_info'
    ];

    /**
     * Добавляем виртуальные атрибуты для сериализации
     */
    protected $appends = [
        'first_name',
        'last_name',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('students')
            ->logExcept(['email', 'phone', 'additional_info'])
            ->setDescriptionForEvent(fn(string $eventName) => "Студент '{$this->name}' был {$eventName}");
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('grade')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Виртуальный атрибут: имя (first_name)
     */
    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', $this->name, 2);
        return $parts[0] ?? '';
    }

    /**
     * Виртуальный атрибут: фамилия (last_name)
     */
    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', $this->name, 2);
        return $parts[1] ?? '';
    }
}
