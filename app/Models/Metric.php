<?php

// app/Models/Metric.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'unit',
        'format',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scenarios()
    {
        return $this->belongsToMany(Scenario::class, 'scenario_metrics')->withPivot('value', 'goal', 'benchmark')->withTimestamps();
    }
}
