<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withPivot('is_admin')->withTimestamps();
    }

    public function scenarios()
    {
        return $this->hasMany(Scenario::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasRole($role)
    {
        return $this->role->slug === $role;
    }

    public function isAdmin(Organization $organization)
    {
        return $this->organizations()->where('organization_id', $organization->id)->wherePivot('is_admin', true)->exists();
    }
}