<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundAttp extends Model
{
    protected $fillable = ['inbound_number', 'attp_file'];
}
