<?php

// app/Models/Strategy.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Strategy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'vision',
        'mission',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function objectives()
    {
        return $this->hasMany(StrategicObjective::class);
    }
}