<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLoanDeduction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'cicilan_ke'         => 'integer',
        'total_tenor'        => 'integer',
        'nominal_potongan'   => 'decimal:2',
        'sisa_pokok_sebelum' => 'decimal:2',
        'sisa_pokok_setelah' => 'decimal:2',
        'is_finalized'       => 'boolean',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function payrollDetail()
    {
        return $this->belongsTo(PayrollDetail::class);
    }

    public function employeeLoan()
    {
        return $this->belongsTo(EmployeeLoan::class);
    }
}
