<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Operator extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function payrollAssignments()
    {
        return $this->hasMany(PayrollOperatorAssignment::class);
    }

    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class);
    }
}
