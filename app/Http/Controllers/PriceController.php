<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\PriceAuditLog;
use App\Models\PriceConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

// Pastikan model PriceConfirmation selalu termuat secara instan (mengatasi isu cache classmap composer di server/cPanel)
if (!class_exists(\App\Models\PriceConfirmation::class, false)) {
    $modelFile = app_path('Models/PriceConfirmation.php');
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
}

class PriceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $role = \Illuminate\Support\Facades\Auth::user()->role;
                if ($role == 'investor' || $role == 'operator') {
                    $action = $request->route()->getActionMethod();
                    if (in_array($action, ['create', 'store', 'edit', 'update', 'destroy'])) {
                        abort(403, 'Anda tidak memiliki hak akses untuk mengubah master harga BBM.');
                    }
                }
            }
            return $next($request);
        });
    }

    /**
     * Memastikan skema tabel price_confirmations tersedia untuk pencatatan SOP bukti penyesuaian harga operator.
     */
    public static function ensureSchema(): void
    {
        static $ensured = false;
        if ($ensured) return;
        $ensured = true;

        if (!\Illuminate\Support\Facades\Schema::hasTable('price_confirmations')) {
            try {
                \Illuminate\Support\Facades\Schema::create('price_confirmations', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('price_id');
                    $table->unsignedBigInteger('shop_id');
                    $table->unsignedBigInteger('operator_id')->nullable();
                    $table->string('status_operator', 30)->default('menunggu_konfirmasi');
                    $table->string('foto_bukti')->nullable();
                    $table->timestamp('dikonfirmasi_pada')->nullable();
                    $table->text('catatan')->nullable();
                    $table->timestamps();

                    $table->foreign('price_id')->references('id')->on('prices')->onDelete('cascade');
                    $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        self::ensureSchema();

        $user = Auth::user();

        // ── 1. Tampilan Khusus Operator (Sembunyikan HPP, Margin & Riwayat Kompleks) ──
        if ($user && $user->role === 'operator') {
            $operator = $user->operator;
            $shop = $operator ? $operator->shop : null;
            $shopId = $shop ? $shop->id : 1;

            // Harga Jual Eceran Aktif saat ini (yang sedang berjalan)
            $activePrice = Price::where(function($q) use ($shopId) {
                    $q->where('shop_id', $shopId)->orWhereNull('shop_id');
                })
                ->where(function($q) {
                    $q->whereNull('effective_at')->orWhere('effective_at', '<=', now());
                })
                ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                ->orderBy('id', 'desc')
                ->first();

            // Harga Terbaru (bisa jadi harga mendatang atau baru ditetapkan oleh Super Admin)
            $latestPrice = Price::where(function($q) use ($shopId) {
                    $q->where('shop_id', $shopId)->orWhereNull('shop_id');
                })
                ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                ->orderBy('id', 'desc')
                ->first();

            $confirmation = null;
            $hasNewPriceUpdate = false;

            if ($latestPrice) {
                $confirmation = PriceConfirmation::where('price_id', $latestPrice->id)
                    ->where('shop_id', $shopId)
                    ->first();

                // Cek harga sebelumnya
                $previousPrice = Price::where(function($q) use ($shopId) {
                        $q->where('shop_id', $shopId)->orWhereNull('shop_id');
                    })
                    ->where('id', '!=', $latestPrice->id)
                    ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                    ->orderBy('id', 'desc')
                    ->first();

                $isPriceChanged = $previousPrice && floatval($previousPrice->harga_jual) != floatval($latestPrice->harga_jual);
                $isUpcoming = $latestPrice->effective_at && \Carbon\Carbon::parse($latestPrice->effective_at)->isFuture();
                $isRecent = $latestPrice->created_at && \Carbon\Carbon::parse($latestPrice->created_at)->diffInDays(now()) <= 14;

                if ($confirmation) {
                    if ($confirmation->status_operator === 'menunggu_konfirmasi') {
                        $hasNewPriceUpdate = true;
                    }
                } else {
                    if ($isPriceChanged || $isUpcoming || ($isRecent && $previousPrice && $latestPrice->id != $previousPrice->id)) {
                        $confirmation = PriceConfirmation::create([
                            'price_id'        => $latestPrice->id,
                            'shop_id'         => $shopId,
                            'status_operator' => 'menunggu_konfirmasi',
                        ]);
                        $hasNewPriceUpdate = true;
                    }
                }
            }

            return view('price.index', compact(
                'operator',
                'shop',
                'activePrice',
                'latestPrice',
                'confirmation',
                'hasNewPriceUpdate'
            ));
        }

        // ── 2. Tampilan Lengkap Super Admin / Admin / Investor ──
        if ($request->ajax()) {
            $query = Price::with(['shop', 'confirmations.operator.user'])->orderBy('effective_at', 'desc');
            if ($user->role == 'investor') {
                $investorShops = ($user->investor && $user->investor->shops) ? $user->investor->shops->pluck('id')->toArray() : [];
                $query->whereIn('shop_id', $investorShops);
            }
            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('shop', function ($row) {
                    return $row->shop ? $row->shop->nama : null;
                })
                ->addColumn('action', function ($row) {
                    $role = Auth::user()->role;
                    if ($role == 'investor') {
                        return '';
                    }

                    $button  = '<a href="' . route('prices.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                    $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="Hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('price.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shops = \App\Models\Shop::all();
        return view('price.create', compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'created_at' => 'required|date',
            'jam' => 'required|date_format:H:i',
            'harga_beli' => 'nullable|numeric',
            'harga_jual' => 'required|numeric',
            'shop_id' => 'required|exists:shops,id',
            'totalisator_perubahan' => 'required|numeric',
            'lokasi_device' => 'nullable|string',
        ]);

        $validated['effective_at'] = \Illuminate\Support\Carbon::parse($validated['created_at'] . ' ' . $validated['jam'])->format('Y-m-d H:i:s');

        // Jika operator tidak mengisi harga beli (karena readonly), ambil harga beli dari harga aktif sebelumnya
        if (empty($validated['harga_beli'])) {
            $lastPrice = Price::where('shop_id', $validated['shop_id'])
                ->where('effective_at', '<=', $validated['effective_at'])
                ->orderBy('effective_at', 'desc')->first();
            $validated['harga_beli'] = $lastPrice ? $lastPrice->harga_beli : 0;
        }

        $price = Price::create($validated);

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $validated['shop_id'] ?? null,
            'action' => 'CREATE',
            'harga_beli_lama' => null,
            'harga_jual_lama' => null,
            'harga_beli_baru' => $validated['harga_beli'],
            'harga_jual_baru' => $validated['harga_jual']
        ]);

        self::ensureSchema();
        if (!empty($validated['shop_id'])) {
            PriceConfirmation::updateOrCreate([
                'price_id' => $price->id,
                'shop_id'  => $validated['shop_id'],
            ], [
                'status_operator' => 'menunggu_konfirmasi',
            ]);
        }

        return redirect()->route('prices.index')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Price $price)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                abort(403, 'Anda tidak dapat mengedit data perubahan harga yang sudah lewat hari (lewat jam 00:00).');
            }
        }

        $shops = \App\Models\Shop::all();
        return view('price.edit', compact('price', 'shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                abort(403, 'Anda tidak dapat mengubah data yang sudah lewat hari.');
            }
        }

        $validated = $request->validate([
            'created_at' => 'required|date',
            'jam' => 'required|date_format:H:i',
            'harga_beli' => 'nullable|numeric',
            'harga_jual' => 'required|numeric',
            'shop_id' => 'required|exists:shops,id',
            'totalisator_perubahan' => 'required|numeric'
        ]);

        $validated['effective_at'] = \Illuminate\Support\Carbon::parse($validated['created_at'] . ' ' . $validated['jam'])->format('Y-m-d H:i:s');

        if (empty($validated['harga_beli'])) {
            $lastPrice = Price::where('shop_id', $validated['shop_id'])
                ->where('effective_at', '<=', $validated['effective_at'])
                ->orderBy('effective_at', 'desc')->first();
            $validated['harga_beli'] = $lastPrice ? $lastPrice->harga_beli : 0;
        }

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $validated['shop_id'] ?? null,
            'action' => 'UPDATE',
            'harga_beli_lama' => $price->harga_beli,
            'harga_jual_lama' => $price->harga_jual,
            'harga_beli_baru' => $validated['harga_beli'],
            'harga_jual_baru' => $validated['harga_jual']
        ]);

        $price->update($validated);

        return redirect()->route('prices.index')->with('success', 'Data berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                return response()->json(['message' => 'Anda tidak dapat menghapus data yang sudah lewat hari.'], 403);
            }
        }

        $harga_beli_lama = $price->harga_beli;
        $harga_jual_lama = $price->harga_jual;
        $shop_id = $price->shop_id;

        $price->delete();

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $shop_id,
            'action' => 'DELETE',
            'harga_beli_lama' => $harga_beli_lama,
            'harga_jual_lama' => $harga_jual_lama,
            'harga_beli_baru' => 0,
            'harga_jual_baru' => 0
        ]);

        return response()->json([
            'message' => 'Data berhasil dihapus.'
        ]);
    }

    /**
     * Store or update price for a shop from the admin dashboard widget.
     * Accepts: shop_id, harga_beli, harga_jual, jam_berlaku (HH:MM), tanggal_berlaku (Y-m-d)
     */
    public function storeFromDashboard(Request $request)
    {
        $validated = $request->validate([
            'shop_id'         => 'required|exists:shops,id',
            'harga_beli'      => 'required|numeric|min:1',
            'harga_jual'      => 'required|numeric|min:1',
            'tanggal_berlaku' => 'required|date',
            'jam_berlaku'     => 'required|date_format:H:i',
        ]);

        $effectiveAt = \Carbon\Carbon::parse(
            $validated['tanggal_berlaku'] . ' ' . $validated['jam_berlaku'] . ':00'
        )->format('Y-m-d H:i:s');

        // Get previous price for audit log
        $prevPrice = Price::where('shop_id', $validated['shop_id'])
            ->where('effective_at', '<=', $effectiveAt)
            ->orderBy('effective_at', 'desc')
            ->first();

        $price = Price::create([
            'shop_id'      => $validated['shop_id'],
            'harga_beli'   => $validated['harga_beli'],
            'harga_jual'   => $validated['harga_jual'],
            'effective_at' => $effectiveAt,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        PriceAuditLog::create([
            'user_id'        => Auth::id(),
            'shop_id'        => $validated['shop_id'],
            'action'         => 'CREATE',
            'harga_beli_lama'=> $prevPrice ? $prevPrice->harga_beli : null,
            'harga_jual_lama'=> $prevPrice ? $prevPrice->harga_jual : null,
            'harga_beli_baru'=> $validated['harga_beli'],
            'harga_jual_baru'=> $validated['harga_jual'],
        ]);

        self::ensureSchema();
        PriceConfirmation::updateOrCreate([
            'price_id' => $price->id,
            'shop_id'  => $validated['shop_id'],
        ], [
            'status_operator' => 'menunggu_konfirmasi',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Harga berhasil diperbarui untuk ' . \App\Models\Shop::find($validated['shop_id'])->nama,
            'price'   => $price,
        ]);
    }

    /**
     * Konfirmasi Penyesuaian Harga Fisik di Dispenser & Display oleh Operator
     */
    public function confirmPriceChange(Request $request)
    {
        self::ensureSchema();

        $request->validate([
            'price_id'              => 'required|exists:prices,id',
            'pernyataan_konfirmasi' => 'required',
            'bukti_perubahan_harga' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',
            'catatan'               => 'nullable|string|max:255',
        ], [
            'pernyataan_konfirmasi.required' => 'Wajib mencentang kotak persetujuan bahwa harga fisik pada mesin dispenser dan totem telah disesuaikan.',
            'bukti_perubahan_harga.required' => 'Wajib melampirkan foto bukti fisik display totem atau mesin dispenser.',
            'bukti_perubahan_harga.image'    => 'Berkas bukti harus berupa gambar/foto.',
            'bukti_perubahan_harga.max'      => 'Ukuran foto maksimal adalah 8MB.',
        ]);

        $user = Auth::user();
        $operator = $user ? $user->operator : null;
        if (!$operator) {
            return redirect()->back()->with('error', 'Hanya akun operator outlet yang dapat mengonfirmasi penyesuaian harga fisik.');
        }

        $shopId = $operator->shop_id;
        $path = null;

        if ($request->hasFile('bukti_perubahan_harga')) {
            $file = $request->file('bukti_perubahan_harga');
            $filename = 'bukti_harga_' . $shopId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('price_confirmations', $filename, 'public');
        }

        PriceConfirmation::updateOrCreate([
            'price_id' => $request->price_id,
            'shop_id'  => $shopId,
        ], [
            'operator_id'       => $operator->id,
            'status_operator'   => 'dikonfirmasi',
            'foto_bukti'        => $path,
            'dikonfirmasi_pada' => now(),
            'catatan'           => $request->input('catatan'),
        ]);

        return redirect()->route('prices.index')->with('success', 'Konfirmasi dan foto bukti penyesuaian harga fisik berhasil dikirim ke Super Admin.');
    }
}
