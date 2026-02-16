<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'kra_pin',
        'address',
        'notes',
    ];

    /**
     * Get the expenses for this vendor.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
