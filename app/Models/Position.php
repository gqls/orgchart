<?php

// app/Models/Position.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'department_id',
        'title',
        'employee_id',
        'grade',
        'function',
        'sub_function',
        'region',
        'country',
        'office',
        'tenure',
        'fully_loaded_cost',
        'cost_center',
        'contract_type',
        'manager_id',
        'name',
    ];

    protected $casts = [
        'fully_loaded_cost' => 'decimal:2',
        'tenure' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function directReports()
    {
        return $this->hasMany(ReportingRelationship::class, 'manager_position_id');
    }

    public function manager()
    {
        return $this->hasOne(ReportingRelationship::class, 'direct_report_position_id');
    }

    public function scenarios()
    {
        return $this->belongsToMany(Scenario::class, 'scenario_positions')->withPivot('status')->withTimestamps();
    }

    /**
     * Get the employees for the organization.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}

