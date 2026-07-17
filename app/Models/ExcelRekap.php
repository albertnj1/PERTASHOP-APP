<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExcelRekap extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'detail_do' => 'array',
        'detail_pengeluaran_rutin' => 'array',
        'detail_pembagian_hasil' => 'array',
    ];

    public function upload()
    {
        return $this->belongsTo(ExcelUpload::class, 'upload_id');
    }
}
