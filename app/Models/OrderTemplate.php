<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class OrderTemplate extends Model
{
    use HasFactory, Auditable;
    protected $table = 'order_templates';
    protected $fillable = [
        'name',
        'description',
        'items', // JSON array of items
    ];
    protected $casts = [
        'items' => 'array',
    ];
}