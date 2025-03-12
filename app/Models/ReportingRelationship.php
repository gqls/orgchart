<?php

// app/Models/ReportingRelationship.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportingRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'manager_position_id',
        'direct_report_position_id',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
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