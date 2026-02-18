<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
class Staff extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;
    protected $table = 'staff';
    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'roles', // REMOVED
        'role',
        'status',
        'email',
        'phone',
        'id_number',
        'salary',
        'bank_name',
        'account_number',
        'designation',
        'is_deleted',
        'permissions',
    ];

    /**
     * Boot the model.
     * Auto-assign default permissions based on role when creating a new staff member.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $staff) {
            // Only assign defaults if permissions is not already set
            if (empty($staff->permissions)) {
                $staff->permissions = config("permissions.defaults.{$staff->role}", []);
            }
        });
    }
    protected $casts = [
        'salary' => 'decimal:2',
        'is_deleted' => 'boolean',
        'permissions' => 'array',
    ];
    
    protected $hidden = [
        'remember_token',
    ];
    
    /**
     * Check if staff has a specific permission
     */
    


    


    


    public function hasPermission(string $permission): bool
    {
        // Admins can do everything
        if ($this->hasRole('admin')) {
            return true;
        }
        // Check if permission exists in permissions array
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if staff has a specific role
     */
    public function hasRole($role): bool
    {
        if (is_array($role)) {
            return $this->hasAnyRole($role);
        }
        return $this->role === $role;
    }

    /**
     * Check if staff has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password_hash);
    }

    /**
     * Set password (hashes automatically)
     */
    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password_hash'] = Hash::make($password);
    }

    /**
     * Tell Laravel's auth system which column holds the password hash.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get staff activity log
     */
    public function activityLog()
    {
        return $this->hasMany(StaffActivityLog::class, 'staff_id');
    }

    public function recurringDeductions()
    {
        return $this->hasMany(StaffRecurringDeduction::class, 'staff_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function recurringEarnings()
    {
        return $this->hasMany(RecurringEarning::class, 'staff_id');
    }

    public function reimbursements()
    {
        return $this->hasMany(StaffReimbursement::class);
    }

    public function pendingReimbursements()
    {
        return $this->hasMany(StaffReimbursement::class)->where('status', 'pending');
    }
}