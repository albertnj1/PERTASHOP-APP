<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'          => 'date',
        'jumlah'           => 'decimal:2',
        'tenor_bulan'      => 'integer',
        'nominal_angsuran' => 'decimal:2',
        'angsuran_ke'      => 'integer',
        'sisa_pokok'       => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deductions()
    {
        return $this->hasMany(PayrollLoanDeduction::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getSisaPokokAttribute($value): float
    {
        $val = floatval($value);
        // Jika sisa pokok di database bernilai 0 padahal belum pernah dicicil (angsuran_ke = 0),
        // otomatis kembalikan nominal awal kasbon (jumlah) agar sisa tagihan tidak keliru 0.
        if ($val <= 0 && (int)($this->attributes['angsuran_ke'] ?? 0) === 0 && !in_array($this->attributes['status'] ?? '', ['rejected'])) {
            return floatval($this->attributes['jumlah'] ?? 0);
        }
        return $val;
    }

    public function isLunas(): bool
    {
        // Kasbon yang belum pernah dicicil sama sekali (angsuran_ke = 0) tidak boleh dianggap lunas
        if ((int)($this->angsuran_ke ?? 0) === 0) {
            return false;
        }

        return $this->status === 'lunas' || (floatval($this->sisa_pokok) <= 0 && !in_array($this->status, ['pending', 'rejected']));
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isLunas()) {
            return 'Lunas';
        }

        return match ($this->status) {
            'approved' => 'Disetujui',
            'active'   => 'Aktif Berjalan',
            'lunas'    => 'Lunas',
            'rejected' => 'Ditolak',
            default    => 'Menunggu Approval',
        };
    }
}
