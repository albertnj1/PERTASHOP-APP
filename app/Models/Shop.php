<?php

namespace App\Models;

use App\Models\Investor;
use App\Models\Operator;
use App\Models\Corporation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shop extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    public function corporation()
    {
        return $this->belongsTo(Corporation::class);
    }
    public function investors()
    {
        return $this->belongsToMany(Investor::class)->withPivot('persentase', 'nominal', 'sub_investors');
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function kolektans()
    {
        return $this->hasMany(Kolektan::class);
    }
}
