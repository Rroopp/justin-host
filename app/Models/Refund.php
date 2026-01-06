<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class Refund extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'pos_sale_id',
        'refund_number',
        'refund_type',
        'status',
        'refund_amount',
        'refund_items',
        'reason',
        'admin_notes',
        'requested_by',
        'approved_by',
        'approved_at',
        'refund_method',
        'reference_number',
        'journal_entry_id',
        'inventory_restored',
        'accounting_reversed',
    ];
    protected $casts = [
        'refund_items' => 'array',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'inventory_restored' => 'boolean',
        'accounting_reversed' => 'boolean',
    ];


    public function posSale()
    {
        return $this->belongsTo(PosSale::class);
    }
    public function requestedBy()
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    /**
     * Generate unique refund number
     */
    }

    public static function generateRefundNumber()
    {
        $year = date('Y');
        $prefix = 'REF';
        
        $lastRefund = self::where('refund_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();
        $sequence = 1;
        if ($lastRefund && preg_match('/-(\d+)$/', $lastRefund->refund_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }
        return sprintf("%s-%s-%04d", $prefix, $year, $sequence);
    }
}