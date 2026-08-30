<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackdateExcelFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'bulan_tahun',
        'original_filename',
        'file_path',
        'file_size',
        'keterangan',
        'user_id',
        'processing_status',
        'processing_result',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processing_result' => 'array',
        'processed_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedPeriodAttribute()
    {
        if (empty($this->bulan_tahun)) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($this->bulan_tahun . '-01')->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return $this->bulan_tahun;
        }
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = (int)$this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
