<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExcelChangeLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function upload()
    {
        return $this->belongsTo(ExcelUpload::class, 'upload_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
