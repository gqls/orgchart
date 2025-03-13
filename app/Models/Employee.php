<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'department_id',
        'position_id',
        'first_name',
        'last_name',
        'email',
        'hire_date',
        'status',
        'employee_id',
        'birth_date',
        'gender',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'salary',
        'bonus',
        'manager_id',
        'notes',
    ];

    protected $dates = [
        'hire_date',
        'birth_date',
    ];

    /**
     * Get the employee's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the organization that the employee belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the department that the employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position that the employee holds.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the employee's manager.
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the employees that report to this employee.
     */
    public function directReports()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }
}