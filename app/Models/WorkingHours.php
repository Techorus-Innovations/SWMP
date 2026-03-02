<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingHours extends Model
{
    protected $fillable = [
        'day',
        'start',
        'end',
        'quantity',
    ];
}
