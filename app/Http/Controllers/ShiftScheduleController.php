<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Operator;
use App\Models\ShiftSchedule;
use App\Models\ShiftSwap;
use App\Models\AttendanceRecap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftScheduleController extends Controller
{
    /**
     * Tampilkan daftar jadwal shift per toko per bulan.
     */
    public function index(Request $request)
    {
        $shops = Shop::all();

        if (Auth::user()->role === 'admin') {
            $shops = Shop::where('id', Auth::user()->admin->shop_id)->get();
        } elseif (Auth::user()->role === 'investor') {
            $shops = Auth::user()->investor?->shops ?? collect();
        }

        $selectedShopId = $request->input('shop_id', $shops->first()?->id);
        $selectedMonth  = $request->input('bulan', Carbon::now()->format('Y-m'));

        [$year, $month] = explode('-', $selectedMonth);

        // Ambil semua operator di toko yang dipilih
        $operators = Operator::with('user')
            ->where('shop_id', $selectedShopId)
            ->get();

        // Ambil semua jadwal bulan ini
        $schedules = ShiftSchedule::with(['operator.user', 'swaps.operatorAsal.user', 'swaps.operatorPengganti.user'])
            ->where('shop_id', $selectedShopId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get()
            ->groupBy(fn ($s) => $s->tanggal->format('Y-m-d'));

        // Rekap kehadiran per operator bulan ini
        $attendanceRecaps = AttendanceRecap::where('shop_id', $selectedShopId)
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->with('operator.user')
            ->get()
            ->keyBy('operator_id');

        // Buat array hari dalam bulan
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $days = collect(range(1, $daysInMonth))->map(fn ($d) => Carbon::createFromDate($year, $month, $d));

        return view('shift_schedules.index', compact(
            'shops',
            'operators',
            'schedules',
            'attendanceRecaps',
            'days',
            'selectedShopId',
            'selectedMonth',
            'year',
            'month',
        ));
    }

    /**
     * Buat jadwal shift baru (single atau bulk).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'operator_id' => 'required|exists:operators,id',
            'tanggal'     => 'required|date',
            'shift_ke'    => 'required|integer|min:1|max:5',
            'keterangan'  => 'nullable|string|max:255',
        ]);

        // Cek apakah slot sudah ada
        $existing = ShiftSchedule::where('operator_id', $validated['operator_id'])
            ->where('tanggal', $validated['tanggal'])
            ->where('shift_ke', $validated['shift_ke'])
            ->first();

        if ($existing) {
            return back()->withErrors(['tanggal' => 'Operator ini sudah memiliki jadwal shift di tanggal dan shift yang sama.'])->withInput();
        }

        ShiftSchedule::create($validated);

        return back()->with('success', 'Jadwal shift berhasil ditambahkan.');
    }

    /**
     * Buat jadwal bulk untuk 1 bulan penuh.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'shop_id'      => 'required|exists:shops,id',
            'operator_id'  => 'required|exists:operators,id',
            'bulan'        => 'required|date_format:Y-m',
            'shift_ke'     => 'required|integer|min:1|max:5',
            'hari_libur'   => 'nullable|array',   // Array tanggal yang dikecualikan
            'hari_libur.*' => 'date',
        ]);

        [$year, $month] = explode('-', $validated['bulan']);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $excludeDates = collect($validated['hari_libur'] ?? []);

        $created = 0;
        $skipped = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day)->toDateString();

            if ($excludeDates->contains($date)) {
                $skipped++;
                continue;
            }

            $exists = ShiftSchedule::where('operator_id', $validated['operator_id'])
                ->where('tanggal', $date)
                ->where('shift_ke', $validated['shift_ke'])
                ->exists();

            if (!$exists) {
                ShiftSchedule::create([
                    'shop_id'     => $validated['shop_id'],
                    'operator_id' => $validated['operator_id'],
                    'tanggal'     => $date,
                    'shift_ke'    => $validated['shift_ke'],
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return back()->with('success', "Berhasil membuat {$created} jadwal shift. {$skipped} di-skip (sudah ada/hari libur).");
    }

    /**
     * Update status shift (izin/sakit/alpha) atau keterangan.
     */
    public function update(Request $request, ShiftSchedule $shiftSchedule)
    {
        $validated = $request->validate([
            'status'     => 'required|in:dijadwalkan,hadir,alpha,izin,sakit',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $shiftSchedule->update($validated);

        return back()->with('success', 'Status jadwal berhasil diperbarui.');
    }

    /**
     * Hapus jadwal shift.
     */
    public function destroy(ShiftSchedule $shiftSchedule)
    {
        $shiftSchedule->delete();
        return response()->json(['message' => 'Jadwal shift dihapus.']);
    }

    /**
     * Proses pergantian operator dalam satu slot shift.
     * Menyimpan riwayat di shift_swaps untuk akuntabilitas.
     */
    public function swap(Request $request, ShiftSchedule $shiftSchedule)
    {
        $validated = $request->validate([
            'operator_pengganti_id' => 'required|exists:operators,id',
            'alasan'                => 'required|in:izin,sakit,keperluan_pribadi,lainnya',
            'keterangan'            => 'nullable|string|max:500',
        ]);

        // Pastikan operator pengganti ada di toko yang sama
        $pengganti = Operator::where('id', $validated['operator_pengganti_id'])
            ->where('shop_id', $shiftSchedule->shop_id)
            ->first();

        if (!$pengganti) {
            return back()->withErrors(['operator_pengganti_id' => 'Operator pengganti harus dari toko yang sama.']);
        }

        // Pastikan operator pengganti tidak bentrok jadwal di tanggal & shift yang sama
        $bentrok = ShiftSchedule::where('operator_id', $validated['operator_pengganti_id'])
            ->where('tanggal', $shiftSchedule->tanggal->toDateString())
            ->where('shift_ke', $shiftSchedule->shift_ke)
            ->where('id', '!=', $shiftSchedule->id)
            ->exists();

        if ($bentrok) {
            return back()->withErrors(['operator_pengganti_id' => 'Operator pengganti sudah memiliki jadwal di slot yang sama. Pilih operator lain.']);
        }

        DB::transaction(function () use ($shiftSchedule, $validated) {
            $operatorAsalId = $shiftSchedule->operator_id;

            // Catat riwayat swap
            ShiftSwap::create([
                'shift_schedule_id'     => $shiftSchedule->id,
                'operator_asal_id'      => $operatorAsalId,
                'operator_pengganti_id' => $validated['operator_pengganti_id'],
                'alasan'                => $validated['alasan'],
                'keterangan'            => $validated['keterangan'] ?? null,
                'diubah_oleh'           => Auth::id(),
                'waktu_perubahan'       => now(),
            ]);

            // Update slot shift ke operator pengganti, kembalikan status ke 'dijadwalkan'
            $shiftSchedule->update([
                'operator_id' => $validated['operator_pengganti_id'],
                'status'      => 'dijadwalkan',
                'keterangan'  => 'Pergantian dari ' . optional(Operator::find($operatorAsalId)->user)->name,
            ]);
        });

        return back()->with('success', 'Pergantian shift berhasil diproses. Riwayat telah dicatat.');
    }

    /**
     * Operator mengajukan tukar shift mandiri (status = pending).
     * Dengan VALIDASI BENTROK JADWAL OPERATOR PENGGANTI.
     */
    public function requestSwap(Request $request)
    {
        $validated = $request->validate([
            'shift_schedule_id'     => 'required|exists:shift_schedules,id',
            'operator_pengganti_id' => 'required|exists:operators,id',
            'alasan'                => 'required|in:izin,sakit,keperluan_pribadi,lainnya',
            'keterangan'            => 'nullable|string|max:500',
        ]);

        $schedule = ShiftSchedule::findOrFail($validated['shift_schedule_id']);
        $operatorAsal = Operator::where('user_id', Auth::id())->first();

        if ($userRole = Auth::user()->role === 'operator' && $operatorAsal && $schedule->operator_id !== $operatorAsal->id) {
            return back()->withErrors(['shift_schedule_id' => 'Anda hanya dapat mengajukan tukar shift untuk jadwal Anda sendiri.']);
        }

        // 1. Validasi operator pengganti dari toko yang sama
        $pengganti = Operator::where('id', $validated['operator_pengganti_id'])
            ->where('shop_id', $schedule->shop_id)
            ->first();

        if (!$pengganti) {
            return back()->withErrors(['operator_pengganti_id' => 'Operator pengganti harus berasal dari toko yang sama.']);
        }

        // 2. Validasi KONFLIK JADWAL: Pastikan operator pengganti TIDAK BENTROK di tanggal & shift_ke yang sama
        $bentrok = ShiftSchedule::where('operator_id', $validated['operator_pengganti_id'])
            ->where('tanggal', $schedule->tanggal->toDateString())
            ->where('shift_ke', $schedule->shift_ke)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($bentrok) {
            return back()->withErrors(['operator_pengganti_id' => 'Operator pengganti (' . optional($pengganti->user)->name . ') sudah memiliki jadwal shift pada tanggal dan jam/shift yang sama.']);
        }

        ShiftSwap::create([
            'shift_schedule_id'     => $schedule->id,
            'operator_asal_id'      => $schedule->operator_id,
            'operator_pengganti_id' => $validated['operator_pengganti_id'],
            'alasan'                => $validated['alasan'],
            'keterangan'            => $validated['keterangan'] ?? null,
            'status'                => 'pending',
            'diubah_oleh'           => Auth::id(),
            'waktu_perubahan'       => now(),
        ]);

        return back()->with('success', 'Pengajuan tukar shift berhasil dikirim. Menunggu persetujuan Admin.');
    }

    /**
     * Admin menyetujui pengajuan tukar shift.
     */
    public function approveSwap(ShiftSwap $swap)
    {
        if ($swap->status !== 'pending') {
            return back()->withErrors(['swap' => 'Pengajuan tukar shift ini sudah diproses sebelumnya.']);
        }

        $schedule = $swap->shiftSchedule;

        // Re-check bentrok jadwal sebelum approve
        $bentrok = ShiftSchedule::where('operator_id', $swap->operator_pengganti_id)
            ->where('tanggal', $schedule->tanggal->toDateString())
            ->where('shift_ke', $schedule->shift_ke)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($bentrok) {
            return back()->withErrors(['swap' => 'Gagal persetujuan: Operator pengganti saat ini sudah memiliki jadwal di slot jam yang sama.']);
        }

        DB::transaction(function () use ($swap, $schedule) {
            $swap->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $schedule->update([
                'operator_id' => $swap->operator_pengganti_id,
                'status'      => 'dijadwalkan',
                'keterangan'  => 'Tukar shift disetujui dari ' . optional($swap->operatorAsal?->user)->name,
            ]);
        });

        return back()->with('success', 'Pengajuan tukar shift berhasil disetujui.');
    }

    /**
     * Admin menolak pengajuan tukar shift.
     */
    public function rejectSwap(ShiftSwap $swap)
    {
        if ($swap->status !== 'pending') {
            return back()->withErrors(['swap' => 'Pengajuan tukar shift ini sudah diproses sebelumnya.']);
        }

        $swap->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan tukar shift telah ditolak.');
    }
}
