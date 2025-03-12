<?php

// app/Models/ScenarioRelationship.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScenarioRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'scenario_id',
        'manager_position_id',
        'direct_report_position_id',
    ];

    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    public function manager()
    {
        return $this->belongsTo(Position::class, 'manager_position_id');
    }

    public function directReport()
    {
        return $this->belongsTo(Position::class, 'direct_report_position_id');
    }
}