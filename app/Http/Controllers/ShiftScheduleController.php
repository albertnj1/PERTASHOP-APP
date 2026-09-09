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

        $selectedShop = Shop::find($selectedShopId) ?? $shops->first();
        $isShift      = $selectedShop?->isShiftBased() ?? false;
        $shiftConfig  = $selectedShop?->getShiftConfig() ?? [];

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
            'selectedShop',
            'isShift',
            'shiftConfig',
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
     * Buat jadwal shift baru (single) atau pencatatan presensi harian non-shift.
     */
    public function store(Request $request)
    {
        $shop = Shop::findOrFail($request->input('shop_id'));
        $isShift = $shop->isShiftBased();
        $shiftConfig = $shop->getShiftConfig();

        if ($isShift) {
            // Cabang bershift (Gumelar & Sumingkir)
            $allowedShifts = array_keys($shiftConfig['shifts'] ?? [1 => [], 2 => []]);
            $validated = $request->validate([
                'shop_id'     => 'required|exists:shops,id',
                'operator_id' => 'required|exists:operators,id',
                'tanggal'     => 'required|date',
                'shift_ke'    => ['required', 'integer', \Illuminate\Validation\Rule::in($allowedShifts)],
                'status'      => 'nullable|in:dijadwalkan,hadir,alpha,izin,sakit',
                'keterangan'  => 'nullable|string|max:255',
            ], [
                'shift_ke.in' => 'Pilihan shift tidak valid untuk cabang ini (Hanya tersedia Shift 1 dan Shift 2).',
            ]);

            $shiftKe    = (int) $validated['shift_ke'];
            $configItem = $shiftConfig['shifts'][$shiftKe] ?? null;
            $jamMulai   = $configItem['jam_mulai'] ?? null;
            $jamSelesai = $configItem['jam_selesai'] ?? null;
            $status     = $validated['status'] ?? 'dijadwalkan';

            // Cek apakah slot sudah ada
            $existing = ShiftSchedule::where('operator_id', $validated['operator_id'])
                ->where('tanggal', $validated['tanggal'])
                ->where('shift_ke', $shiftKe)
                ->first();

            if ($existing) {
                $existing->update([
                    'status'      => $status,
                    'jam_mulai'   => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'keterangan'  => $validated['keterangan'] ?? $existing->keterangan,
                ]);
                return back()->with('success', "Jadwal Shift {$shiftKe} untuk tanggal " . Carbon::parse($validated['tanggal'])->translatedFormat('d M Y') . " berhasil diperbarui.");
            }

            ShiftSchedule::create([
                'shop_id'     => $validated['shop_id'],
                'operator_id' => $validated['operator_id'],
                'tanggal'     => $validated['tanggal'],
                'shift_ke'    => $shiftKe,
                'jam_mulai'   => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'status'      => $status,
                'keterangan'  => $validated['keterangan'] ?? null,
            ]);

            return back()->with('success', 'Jadwal shift berhasil ditambahkan.');
        } else {
            // Cabang non-shift (Kemutug Lor, Kalitapen, Kalibenda, Pageralang)
            $validated = $request->validate([
                'shop_id'     => 'required|exists:shops,id',
                'operator_id' => 'required|exists:operators,id',
                'tanggal'     => 'required|date',
                'status'      => 'nullable|in:dijadwalkan,hadir,alpha,izin,sakit',
                'keterangan'  => 'nullable|string|max:255',
            ]);

            $status     = $validated['status'] ?? 'hadir';
            $jamMulai   = '07:00:00';
            $jamSelesai = '21:00:00';

            $existing = ShiftSchedule::where('operator_id', $validated['operator_id'])
                ->where('tanggal', $validated['tanggal'])
                ->where('shift_ke', 1)
                ->first();

            if ($existing) {
                $existing->update([
                    'status'      => $status,
                    'jam_mulai'   => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'keterangan'  => $validated['keterangan'] ?? $existing->keterangan,
                ]);
                return back()->with('success', 'Presensi harian operator berhasil diperbarui.');
            }

            ShiftSchedule::create([
                'shop_id'     => $validated['shop_id'],
                'operator_id' => $validated['operator_id'],
                'tanggal'     => $validated['tanggal'],
                'shift_ke'    => 1,
                'jam_mulai'   => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'status'      => $status,
                'keterangan'  => $validated['keterangan'] ?? null,
            ]);

            return back()->with('success', 'Presensi harian operator berhasil dicatat.');
        }
    }

    /**
     * Buat jadwal bulk untuk 1 bulan penuh (dengan opsi pola rotasi otomatis).
     * HANYA AKTIF untuk cabang bershift (Gumelar dan Sumingkir).
     */
    public function bulkStore(Request $request)
    {
        $shop = Shop::findOrFail($request->input('shop_id'));

        if (!$shop->isShiftBased()) {
            return back()->withErrors([
                'shop_id' => "Cabang {$shop->nama} menggunakan sistem jam kerja harian biasa (non-shift). Fitur generator pola shift otomatis hanya tersedia untuk cabang Gumelar dan Sumingkir."
            ])->withInput();
        }

        $shiftConfig   = $shop->getShiftConfig();
        $allowedShifts = array_keys($shiftConfig['shifts'] ?? [1 => [], 2 => []]);

        $validated = $request->validate([
            'shop_id'      => 'required|exists:shops,id',
            'operator_id'  => 'required|exists:operators,id',
            'bulan'        => 'required|date_format:Y-m',
            'shift_ke'     => ['nullable', 'integer', \Illuminate\Validation\Rule::in($allowedShifts)],
            'pola'         => 'nullable|string|in:fixed,2p_2s_1l,full_pagi,full_siang',
            'hari_libur'   => 'nullable|array',
            'hari_libur.*' => 'date',
        ], [
            'shift_ke.in' => 'Nomor shift tidak valid untuk cabang ini.',
        ]);

        [$year, $month] = explode('-', $validated['bulan']);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $excludeDates = collect($validated['hari_libur'] ?? []);
        $pola = $validated['pola'] ?? 'fixed';
        $defaultShift = $validated['shift_ke'] ?? 1;

        $created = 0;
        $skipped = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $carbonDate = Carbon::createFromDate($year, $month, $day);
            $date = $carbonDate->toDateString();

            if ($excludeDates->contains($date)) {
                $skipped++;
                continue;
            }

            // Hitung shift berdasarkan pola jika dipilih
            $shiftToAssign = $defaultShift;
            if ($pola === '2p_2s_1l') {
                // Pola 5 hari: Hari 1-2 Shift 1, Hari 3-4 Shift 2, Hari 5 Libur
                $cycleIndex = ($day - 1) % 5;
                if ($cycleIndex === 0 || $cycleIndex === 1) {
                    $shiftToAssign = 1;
                } elseif ($cycleIndex === 2 || $cycleIndex === 3) {
                    $shiftToAssign = 2;
                } else {
                    // Libur
                    continue;
                }
            } elseif ($pola === 'full_pagi') {
                $shiftToAssign = 1;
            } elseif ($pola === 'full_siang') {
                $shiftToAssign = 2;
            }

            $exists = ShiftSchedule::where('operator_id', $validated['operator_id'])
                ->where('tanggal', $date)
                ->where('shift_ke', $shiftToAssign)
                ->exists();

            if (!$exists) {
                $configItem = $shiftConfig['shifts'][$shiftToAssign] ?? null;
                $jamMulai   = $configItem['jam_mulai'] ?? null;
                $jamSelesai = $configItem['jam_selesai'] ?? null;

                ShiftSchedule::create([
                    'shop_id'     => $validated['shop_id'],
                    'operator_id' => $validated['operator_id'],
                    'tanggal'     => $date,
                    'shift_ke'    => $shiftToAssign,
                    'jam_mulai'   => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'status'      => 'dijadwalkan',
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return back()->with('success', "Berhasil membuat {$created} jadwal shift untuk cabang {$shop->nama}. {$skipped} di-skip (sudah ada/hari libur).");
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
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Jadwal shift dihapus.']);
        }
        return back()->with('success', 'Jadwal shift berhasil dihapus.');
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
