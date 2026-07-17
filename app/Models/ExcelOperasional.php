<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExcelOperasional extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function upload()
    {
        return $this->belongsTo(ExcelUpload::class, 'upload_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}
