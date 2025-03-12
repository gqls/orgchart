<?php

// app/Models/Scenario.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scenario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'description',
        'is_current',
        'is_base',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'is_base' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'scenario_positions')->withPivot('status')->withTimestamps();
    }

    public function relationships()
    {
        return $this->hasMany(ScenarioRelationship::class);
    }

    public function metrics()
    {
        return $this->belongsToMany(Metric::class, 'scenario_metrics')->withPivot('value', 'goal', 'benchmark')->withTimestamps();
    }

    public function calculateMetrics()
    {
        // This would contain logic to calculate all metrics for the scenario
        // For example, headcount, spans of control, layers, etc.
    }
}
