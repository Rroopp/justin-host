<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Staff|null $staff
 * @property-read Location|null $setLocation
 * @property-read \Illuminate\Database\Eloquent\Collection|SurgeryUsageItem[] $items
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo user()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo staff()
 */
class SurgeryUsage extends Model
{
    use HasFactory;

    protected $table = 'surgery_usage';

    protected $fillable = [
        'surgery_date',
        'patient_name',
        'patient_number',
        'surgeon_name',
        'facility_name',
        'set_location_id',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'surgery_date' => 'date'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SurgeryUsageItem::class, 'surgery_usage_id');
    }

    public function setLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'set_location_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'user_id');
    }

    /**
     * Alias for staff relationship for backward compatibility.
     * @deprecated Use staff() instead.
     */
    public function user(): BelongsTo
    {
        return $this->staff();
    }
}
