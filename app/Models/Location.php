<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'contact_phone',
        'address',
        'is_active',
        'asset_id',
        'rental_price',
    ];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function setContents()
    {
        return $this->hasMany(SetContent::class);
    }

    public function surgicalSet()
    {
        return $this->hasOne(SurgicalSet::class);
    }

    public function isSet()
    {
        return $this->type === 'set';
    }

    /**
     * Consignment-related methods
     */
    public function isConsignment()
    {
        return $this->type === 'consignment';
    }

    public function consignmentStock()
    {
        return $this->hasMany(ConsignmentStockLevel::class);
    }

    public function consignmentTransactions()
    {
        return $this->hasMany(ConsignmentTransaction::class);
    }

    public function unbilledTransactions()
    {
        return $this->consignmentTransactions()->unbilled();
    }

    public function stockAging($days = 90)
    {
        return $this->consignmentStock()->aging($days);
    }

    public function getTotalConsignmentValueAttribute()
    {
        return $this->consignmentStock()->get()->sum('value');
    }

    public function getUnbilledAmountAttribute()
    {
        return $this->unbilledTransactions()->get()->sum('value');
    }
}
