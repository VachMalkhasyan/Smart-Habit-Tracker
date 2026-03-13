<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabitTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'category', 'icon',
        'goal', 'goal_unit', 'repeat_count',
        'deadline_value', 'deadline_unit',
        'priority', 'sort_order'
    ];
}
