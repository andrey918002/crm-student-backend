<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'phone'];

    public function groups()
    {
        return $this->belongsToMany(Group::class)->withPivot('grade')->withTimestamps();
    }
}
