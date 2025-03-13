<?php

// app/Models/Organization.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'primary_color',
        'secondary_color',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('is_admin')->withTimestamps();
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function reportingRelationships()
    {
        return $this->hasMany(ReportingRelationship::class);
    }

    public function scenarios()
    {
        return $this->hasMany(Scenario::class);
    }

    public function strategies()
    {
        return $this->hasMany(Strategy::class);
    }

    public function metrics()
    {
        return $this->hasMany(Metric::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getCurrentScenario()
    {
        return $this->scenarios()->where('is_current', true)->first();
    }

    public function getBaseScenario()
    {
        return $this->scenarios()->where('is_base', true)->first();
    }

    /**
     * Get the employees for the organization.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
