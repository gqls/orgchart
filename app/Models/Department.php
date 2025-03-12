<?php

// app/Models/Department.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'color',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}
