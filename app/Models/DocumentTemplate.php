<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class DocumentTemplate extends Model
{
    use HasFactory, Auditable;
    protected $table = 'document_templates';
    protected $fillable = [
        'template_type',
        'template_name',
        'template_data',
        'is_default',
    ];
    protected $casts = [
        'template_data' => 'array',
        'is_default' => 'boolean',
    ];
}