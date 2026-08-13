<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public static function log(
        string $action,
        string $modelType,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): static {
        return static::create([
            'user_id'     => \Illuminate\Support\Facades\Auth::id(),
            'action'      => $action,
            'model_type'  => $modelType,
            'model_id'    => $modelId,
            'description' => $description,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
