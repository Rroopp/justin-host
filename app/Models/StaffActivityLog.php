<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class StaffActivityLog extends Model
{
    use HasFactory, Auditable;
    protected $table = 'staff_activity_log';
    protected $fillable = [
        'staff_id',
        'username',
        'activity_type',
        'description',
        'ip_address',
        'metadata',
    ];
    protected $casts = [
        'metadata' => 'array',
    /**
     * Get the staff member that owns this activity
     */
    ];


    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}