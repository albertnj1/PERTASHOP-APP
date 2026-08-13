<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportAuditLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'decision_log'        => 'array',
        'performance_metrics' => 'array',
        'confidence_breakdown'=> 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
