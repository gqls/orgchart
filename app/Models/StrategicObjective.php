<?php

// app/Models/StrategicObjective.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicObjective extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'strategy_id',
        'name',
        'description',
    ];

    public function strategy()
    {
        return $this->belongsTo(Strategy::class);
    }
}
