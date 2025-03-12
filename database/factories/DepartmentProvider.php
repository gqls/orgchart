<?php

// Add custom faker providers for department names
namespace Database\Factories;

use Faker\Provider\Base;

class DepartmentProvider extends Base
{
    protected static $departments = [
        'Finance', 'Human Resources', 'Marketing', 'Sales', 'Operations',
        'Information Technology', 'Research & Development', 'Legal',
        'Customer Support', 'Administration', 'Production', 'Quality Assurance',
        'Business Development', 'Public Relations', 'Supply Chain', 'Engineering',
        'Product Management', 'Facilities', 'Strategy', 'Analytics'
    ];

    public function department()
    {
        return static::randomElement(static::$departments);
    }
}
