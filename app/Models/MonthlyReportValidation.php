<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyReportValidation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function monthlyReport()
    {
        return $this->belongsTo(MonthlyReport::class);
    }
}
