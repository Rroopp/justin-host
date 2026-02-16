<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Shareholder extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'ownership_percentage',
        'capital_account_id',
        'drawings_account_id',
        'staff_id',
    ];
    /**
     * Get the capital account associated with the shareholder.
     */
    


    


    


    public function capitalAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'capital_account_id');
    }
     /**
     * Get the drawings account associated with the shareholder.
     */
    public function drawingsAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'drawings_account_id');
     /**
     * Get the staff member associated with the shareholder.
     */
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class);
    }
}