<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    const STATUS_BEFORE_WORK = 'before_work';
    const STATUS_WORKING = 'working';
    const STATUS_ON_BREAK = 'on_break';
    const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function activeBreakTime()
    {
        return $this->hasOne(BreakTime::class)->whereNull('break_end_at');
    }
}
