<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Price;
use App\Models\Spending;
use App\Models\DailyReport;
use App\Models\Incoming;
use App\Models\TestPump;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\PriceAuditLog;
use App\Models\Kolektan;
use Yajra\DataTables\Facades\DataTables;

class DailyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
            } elseif (Auth::user()->role == 'operator') {
                $shop_id = Auth::user()->operator->shop_id;
            } else {
                $shop_id = $request->input('shop_id', 1);
            }

            $data = DailyReport::with(['operator.user', 'spendings', 'incomings', 'testPumps'])->where('shop_id', $shop_id)->latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($data) {

                    if (Auth::user()->role == 'operator') {
                        $lastRow = $data->first(); // Mendapatkan data terakhir dari koleksi
                        $button = '';

                        if ($row->id === $lastRow->id && $row->operator_id === Auth::user()->operator->id) { // Menambahkan tombol hanya pada data terakhir
                            $button = '<a href="' . route('daily-reports.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                            $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } elseif (Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin') {
                        $button = '<a href="' . route('daily-reports.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    } else {
                        $button = '';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();

        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }

        return view('daily_report.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if (Auth::user()->role !== 'operator' || !Auth::user()->operator) {
            return redirect()->route('daily-reports.index')->with('error', 'Hanya operator yang dapat membuat laporan harian.');
        }

        #get shop id for operator login
        $shop_id = Auth::user()->operator->shop_id;

        // Resolve shop-specific active price based on dynamic shop_id and effective_at (with global fallback)
        $latest_price = Price::where('shop_id', $shop_id)
            ->where('effective_at', '<=', Carbon::now())
            ->orderBy('effective_at', 'desc')
            ->first();

        if (!$latest_price) {
            $latest_price = Price::whereNull('shop_id')
                ->where('effective_at', '<=', Carbon::now())
                ->orderBy('effective_at', 'desc')
                ->first();
        }

        $harga = $latest_price ? $latest_price->harga_jual : 0;

        $shop = Shop::find($shop_id);

        $operator_id = Auth::user()->operator->id;

        $latest_daily_report = DailyReport::where('shop_id', $shop_id)
            ->latest()
            ->orderBy('totalisator_akhir', 'desc')
            ->first();
        $totalisator_awal =  $latest_daily_report ? $latest_daily_report->totalisator_akhir : Shop::find($shop_id)->totalisator_awal;
        $stik_awal =  $latest_daily_report ? $latest_daily_report->stik_akhir : Shop::find($shop_id)->stik_awal;

        $latest_daily_report_by_operator = DailyReport::where('operator_id', $operator_id)
            ->latest()
            ->orderBy('totalisator_akhir', 'desc')
            ->first();

        $belum_disetorkan = $latest_daily_report_by_operator ? $latest_daily_report_by_operator->belum_disetorkan : 0;

        //ajax get total spendings where operator_id = $operator_id and created_at = now() and $daily_report_id = null
        if ($request->ajax()) {
            $spendings = Spending::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->where('operator_id', $operator_id)
                ->where('daily_report_id', null)
                ->get();
            $incomings = Incoming::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->where('operator_id', $operator_id)
                ->where('daily_report_id', null)
                ->get();
            $testPumps = TestPump::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->where('operator_id', $operator_id)
                ->where('daily_report_id', null)
                ->get();
            $total_spendings = $spendings->sum('jumlah');
            $total_incomings = $incomings->sum('volume');
            $total_test_pumps = $testPumps->sum('volume');
            return response()->json(compact('total_spendings', 'total_incomings', 'total_test_pumps'));
        }

        $prices = Price::latest()->get();

        $today = Carbon::now()->format('Y-m-d');
        $todayPriceChanges = Price::where('shop_id', $shop_id)
            ->whereDate('effective_at', $today)
            ->orderBy('effective_at', 'asc')
            ->get();

        $pendingPurchases = \App\Models\Purchase::where('shop_id', $shop_id)
            ->get()
            ->filter(function ($p) {
                return $p->sisa > 0;
            });

        $kolektans = Kolektan::where('shop_id', $shop_id)->get();

        return view('daily_report.create', compact('harga', 'shop', 'totalisator_awal', 'stik_awal', 'belum_disetorkan', 'prices', 'todayPriceChanges', 'pendingPurchases', 'kolektans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validated = $request->validate([
            'totalisator_awal' => 'required|numeric',
            'totalisator_akhir' => 'required|numeric',
            'stik_awal' => 'required|numeric',
            'stik_akhir' => 'required|numeric',
            'test_pump_volume' => 'nullable|numeric',
            'penerimaan_volume' => 'nullable|numeric',
            'setor_tunai' => 'nullable|numeric',
            'setor_qris' => 'nullable|numeric',
            'setor_transfer' => 'nullable|numeric',
            'setor_kolektan' => 'nullable|numeric',
            'kolektan_id' => 'nullable|exists:kolektans,id',
            'kolektan_pin' => 'nullable|string',
        ], $customMessages);

        if (floatval($request->input('setor_kolektan')) > 0) {
            $request->validate([
                'kolektan_id' => 'required',
                'kolektan_pin' => 'required'
            ], [
                'kolektan_id.required' => 'Pilih Kolektan jika ada setoran ke Kolektan.',
                'kolektan_pin.required' => 'PIN Kolektan wajib diisi jika ada setoran ke Kolektan.'
            ]);

            $kolektan = Kolektan::find($request->input('kolektan_id'));
            if (!$kolektan || $kolektan->pin !== $request->input('kolektan_pin')) {
                return back()->withErrors(['kolektan_pin' => 'PIN Kolektan salah! Setoran tidak dapat diproses.'])->withInput();
            }
            $validated['waktu_kolektan'] = Carbon::now()->format('Y-m-d H:i:s');
        }

        //get shop id for admin or operator login
        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin->shop_id;
        } elseif (Auth::user()->role == 'operator') {
            $shop_id = Auth::user()->operator->shop_id;
        } else {
            $shop_id = 1;
        }

        $operator_id = Auth::user()->operator->id;
        $validated['shop_id'] = $shop_id;
        $validated['operator_id'] = $operator_id;

        $validated['test_pump_volume'] = floatval($request->input('test_pump_volume', 0));
        $validated['penerimaan_volume'] = floatval($request->input('penerimaan_volume', 0));
        $validated['setor_tunai'] = floatval($request->input('setor_tunai', 0));
        $validated['setor_qris'] = floatval($request->input('setor_qris', 0));
        $validated['setor_transfer'] = floatval($request->input('setor_transfer', 0));
        $validated['setor_kolektan'] = floatval($request->input('setor_kolektan', 0));

        unset($validated['kolektan_pin']);

        // Ambil tanggal laporan dari request tanggal, jika tidak ada gunakan waktu saat ini
        $reportDate = $request->input('tanggal') 
            ? Carbon::parse($request->input('tanggal'))->format('Y-m-d H:i:s') 
            : Carbon::now()->format('Y-m-d H:i:s');

        $validated['created_at'] = $reportDate;

        // Validasi lonjakan totalisator (max 5000 Liter, min 0)
        $latest_daily_report = DailyReport::where('shop_id', $shop_id)
            ->latest()
            ->orderBy('totalisator_akhir', 'desc')
            ->first();
        $totalisator_awal = $latest_daily_report ? floatval($latest_daily_report->totalisator_akhir) : floatval(Shop::find($shop_id)->totalisator_awal);
        $totalisator_akhir = floatval($request->input('totalisator_akhir'));
        $diff = $totalisator_akhir - $totalisator_awal;

        if ($diff > 5000 || $diff < 0) {
            return back()->withErrors([
                'totalisator_akhir' => 'Selisih totalisator tidak wajar (' . number_format($diff, 3) . ' L). Periksa kembali input Anda atau laporkan ke Admin.'
            ])->withInput();
        }

        // Cari harga yang berlaku pada tanggal laporan tersebut sesuai outlet
        $applicablePrice = Price::where('shop_id', $shop_id)
            ->where('effective_at', '<=', $reportDate)
            ->orderBy('effective_at', 'desc')
            ->first();

        if (!$applicablePrice) {
            $applicablePrice = Price::whereNull('shop_id')
                ->where('effective_at', '<=', $reportDate)
                ->orderBy('effective_at', 'desc')
                ->first();
        }

        if (!$applicablePrice) {
            $applicablePrice = Price::latest()->first();
        }

        $validated['price_id'] = $applicablePrice->id;
        $validated['stok_awal'] = floatval($validated['stik_awal']) * Shop::find($shop_id)->skala;

        //create daily report
        $dailyReport = DailyReport::create($validated);

        // Sync test pump
        if ($validated['test_pump_volume'] > 0) {
            TestPump::create([
                'daily_report_id' => $dailyReport->id,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'totalisator_awal' => $totalisator_awal,
                'totalisator_akhir' => $totalisator_awal + $validated['test_pump_volume'],
                'volume' => $validated['test_pump_volume'],
                'created_at' => $reportDate
            ]);
        }

        // Sync incoming BBM
        if ($validated['penerimaan_volume'] > 0) {
            Incoming::create([
                'daily_report_id' => $dailyReport->id,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'volume' => $validated['penerimaan_volume'],
                'stik_awal' => $validated['stik_awal'],
                'stik_akhir' => $validated['stik_akhir'],
                'sopir' => '-',
                'no_polisi' => '-',
                'created_at' => $reportDate
            ]);
        }

        // Auto-create Incomings if Operator checked pending SOs (legacy)
        if ($request->has('received_purchases') && is_array($request->received_purchases)) {
            foreach ($request->received_purchases as $purchase_id) {
                $purchase = \App\Models\Purchase::find($purchase_id);
                if ($purchase && $purchase->sisa > 0) {
                    Incoming::create([
                        'daily_report_id' => $dailyReport->id,
                        'shop_id' => $shop_id,
                        'operator_id' => $operator_id,
                        'purchase_id' => $purchase_id,
                        'incoming_date' => Carbon::parse($reportDate)->format('Y-m-d'),
                        'sopir' => '-',
                        'no_polisi' => '-',
                        'volume' => $purchase->sisa,
                        'stik_awal' => $request->input('stik_awal'),
                        'stik_akhir' => $request->input('stik_akhir'),
                        'penerimaan_real' => $purchase->sisa,
                        'created_at' => Carbon::parse($reportDate)->format('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        // Sync spendings
        $spendingsInput = $request->input('spendings', []);
        foreach ($spendingsInput as $categoryId => $amount) {
            $amount = floatval(str_replace(',', '', $amount));
            if ($amount > 0) {
                Spending::create([
                    'daily_report_id' => $dailyReport->id,
                    'spending_category_id' => $categoryId,
                    'shop_id' => $shop_id,
                    'operator_id' => $operator_id,
                    'jumlah' => $amount,
                    'created_at' => $reportDate
                ]);
            }
        }

        // Handle Lain-lain
        $lainAmt = floatval(str_replace(',', '', $request->input('spending_lain_nom', 0)));
        $lainKet = $request->input('spending_lain_ket', '');
        if ($lainAmt > 0) {
            Spending::create([
                'daily_report_id' => $dailyReport->id,
                'spending_category_id' => 99,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'jumlah' => $lainAmt,
                'keterangan' => $lainKet ?: 'Lain-lain',
                'created_at' => $reportDate
            ]);
        }

        // Simpan data perubahan harga jika diinput oleh operator
        $this->processPriceChanges($dailyReport, $request);

        return to_route('daily-reports.index')->with('success', 'Data laporan harian berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyReport $dailyReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, DailyReport $dailyReport)
    {
        $prices = Price::latest()->get();
        
        // Load spendings for form population
        $spendings = Spending::where('daily_report_id', $dailyReport->id)
            ->get()
            ->keyBy('spending_category_id');

        $kolektans = Kolektan::where('shop_id', $dailyReport->shop_id)->get();

        return view('daily_report.edit', compact('dailyReport', 'prices', 'spendings', 'kolektans'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyReport $dailyReport)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validated = $request->validate([
            'totalisator_awal' => 'required|numeric',
            'totalisator_akhir' => 'required|numeric',
            'stik_awal' => 'required|numeric',
            'stik_akhir' => 'required|numeric',
            'test_pump_volume' => 'nullable|numeric',
            'penerimaan_volume' => 'nullable|numeric',
            'setor_tunai' => 'nullable|numeric',
            'setor_qris' => 'nullable|numeric',
            'setor_transfer' => 'nullable|numeric',
            'setor_kolektan' => 'nullable|numeric',
            'kolektan_id' => 'nullable|exists:kolektans,id',
            'kolektan_pin' => 'nullable|string',
            'diverifikasi' => 'boolean'
        ], $customMessages);

        if (floatval($request->input('setor_kolektan')) > 0) {
            // Only require PIN if the amount changed or it was not previously set
            if (floatval($request->input('setor_kolektan')) != floatval($dailyReport->setor_kolektan)) {
                $request->validate([
                    'kolektan_id' => 'required',
                    'kolektan_pin' => 'required'
                ], [
                    'kolektan_id.required' => 'Pilih Kolektan jika ada setoran ke Kolektan.',
                    'kolektan_pin.required' => 'PIN Kolektan wajib diisi karena nominal setoran berubah.'
                ]);

                $kolektan = Kolektan::find($request->input('kolektan_id'));
                if (!$kolektan || $kolektan->pin !== $request->input('kolektan_pin')) {
                    return back()->withErrors(['kolektan_pin' => 'PIN Kolektan salah! Setoran tidak dapat diproses.'])->withInput();
                }
                $validated['waktu_kolektan'] = Carbon::now()->format('Y-m-d H:i:s');
            }
        }

        $totalisator_awal = floatval($request->input('totalisator_awal'));
        $totalisator_akhir = floatval($request->input('totalisator_akhir'));
        $diff = $totalisator_akhir - $totalisator_awal;

        if ($diff > 5000 || $diff < 0) {
            return back()->withErrors([
                'totalisator_akhir' => 'Selisih totalisator tidak wajar (' . number_format($diff, 3) . ' L). Periksa kembali input Anda atau laporkan ke Admin.'
            ])->withInput();
        }

        $operator_id = $dailyReport->operator_id;
        $shop_id = $dailyReport->shop_id;
        $reportDate = $dailyReport->created_at->format('Y-m-d H:i:s');

        $validated['test_pump_volume'] = floatval($request->input('test_pump_volume', 0));
        $validated['penerimaan_volume'] = floatval($request->input('penerimaan_volume', 0));
        $validated['setor_tunai'] = floatval($request->input('setor_tunai', 0));
        $validated['setor_qris'] = floatval($request->input('setor_qris', 0));
        $validated['setor_transfer'] = floatval($request->input('setor_transfer', 0));
        $validated['setor_kolektan'] = floatval($request->input('setor_kolektan', 0));

        unset($validated['kolektan_pin']);

        //update daily report
        $dailyReport->update($validated);

        // Update test pump
        TestPump::where('daily_report_id', $dailyReport->id)->delete();
        if ($validated['test_pump_volume'] > 0) {
            TestPump::create([
                'daily_report_id' => $dailyReport->id,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'totalisator_awal' => $totalisator_awal,
                'totalisator_akhir' => $totalisator_awal + $validated['test_pump_volume'],
                'volume' => $validated['test_pump_volume'],
                'created_at' => $reportDate
            ]);
        }

        // Update incoming BBM
        Incoming::where('daily_report_id', $dailyReport->id)->delete();
        if ($validated['penerimaan_volume'] > 0) {
            Incoming::create([
                'daily_report_id' => $dailyReport->id,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'volume' => $validated['penerimaan_volume'],
                'stik_awal' => $validated['stik_awal'],
                'stik_akhir' => $validated['stik_akhir'],
                'sopir' => '-',
                'no_polisi' => '-',
                'created_at' => $reportDate
            ]);
        }

        // Update spendings
        Spending::where('daily_report_id', $dailyReport->id)->delete();
        $spendingsInput = $request->input('spendings', []);
        foreach ($spendingsInput as $categoryId => $amount) {
            $amount = floatval(str_replace(',', '', $amount));
            if ($amount > 0) {
                Spending::create([
                    'daily_report_id' => $dailyReport->id,
                    'spending_category_id' => $categoryId,
                    'shop_id' => $shop_id,
                    'operator_id' => $operator_id,
                    'jumlah' => $amount,
                    'created_at' => $reportDate
                ]);
            }
        }

        // Handle Lain-lain
        $lainAmt = floatval(str_replace(',', '', $request->input('spending_lain_nom', 0)));
        $lainKet = $request->input('spending_lain_ket', '');
        if ($lainAmt > 0) {
            Spending::create([
                'daily_report_id' => $dailyReport->id,
                'spending_category_id' => 99,
                'shop_id' => $shop_id,
                'operator_id' => $operator_id,
                'jumlah' => $lainAmt,
                'keterangan' => $lainKet ?: 'Lain-lain',
                'created_at' => $reportDate
            ]);
        }

        // Simpan data perubahan harga jika diinput oleh operator
        $this->processPriceChanges($dailyReport, $request);

        return to_route('daily-reports.index')->with('success', 'Data laporan harian berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReport $dailyReport)
    {
        $dailyReport->delete();
        return response()->json([
            'message' => 'Data laporan harian telah dihapus.'
        ]);
    }

    /**
     * Helper to process price changes input and build segments automatically.
     */
    private function processPriceChanges(DailyReport $dailyReport, Request $request)
    {
        $dailyReport->periods()->delete();

        if ($request->has('price_changes') && is_array($request->input('price_changes'))) {
            $priceChanges = $request->input('price_changes');
            
            $validChanges = [];
            foreach ($priceChanges as $change) {
                if (!empty($change['jam']) && !empty($change['harga_jual']) && isset($change['totalisator'])) {

                    // Auto-fetch harga_beli from the active price for this outlet
                    $reportDateOnly = Carbon::parse($dailyReport->created_at)->format('Y-m-d');
                    $changeTimestamp = Carbon::parse($reportDateOnly . ' ' . $change['jam'])->format('Y-m-d H:i:s');

                    $activePriceForBeli = Price::where('shop_id', $dailyReport->shop_id)
                        ->where('effective_at', '<=', $changeTimestamp)
                        ->orderBy('effective_at', 'desc')
                        ->first();
                    if (!$activePriceForBeli) {
                        $activePriceForBeli = Price::whereNull('shop_id')
                            ->where('effective_at', '<=', $changeTimestamp)
                            ->orderBy('effective_at', 'desc')
                            ->first();
                    }
                    $harga_beli = $activePriceForBeli ? floatval($activePriceForBeli->harga_beli) : floatval($change['harga_beli'] ?? 0);

                    $validChanges[] = [
                        'jam'         => $change['jam'],
                        'harga_jual'  => floatval($change['harga_jual']),
                        'harga_beli'  => $harga_beli,
                        'totalisator' => floatval($change['totalisator']),
                    ];
                }
            }

            if (empty($validChanges)) {
                return;
            }

            // Sort changes by totalisator reading
            usort($validChanges, function($a, $b) {
                return $a['totalisator'] <=> $b['totalisator'];
            });

            $latest_daily_report = DailyReport::where('shop_id', $dailyReport->shop_id)
                ->where('created_at', '<', $dailyReport->created_at)
                ->latest()
                ->first();
            $totalisator_awal = $latest_daily_report ? floatval($latest_daily_report->totalisator_akhir) : floatval(Shop::find($dailyReport->shop_id)->totalisator_awal);
            $totalisator_akhir = floatval($dailyReport->totalisator_akhir);

            $currentAwal = $totalisator_awal;
            $lastPriceId = $dailyReport->price_id;

            foreach ($validChanges as $change) {
                $changeTotalisator = $change['totalisator'];

                if ($changeTotalisator > $currentAwal && $changeTotalisator < $totalisator_akhir) {
                    $reportDateOnly = Carbon::parse($dailyReport->created_at)->format('Y-m-d');
                    $changeTimestamp = Carbon::parse($reportDateOnly . ' ' . $change['jam'])->format('Y-m-d H:i:s');

                    $price = Price::firstOrCreate(
                        [
                            'shop_id' => $dailyReport->shop_id,
                            'harga_beli' => $change['harga_beli'],
                            'harga_jual' => $change['harga_jual'],
                            'effective_at' => $changeTimestamp
                        ],
                        [
                            'created_at' => $changeTimestamp,
                            'updated_at' => $changeTimestamp
                        ]
                    );

                    if ($price->wasRecentlyCreated) {
                        $prev_price = Price::where('shop_id', $dailyReport->shop_id)
                            ->where('effective_at', '<', $changeTimestamp)
                            ->orderBy('effective_at', 'desc')
                            ->first();
                        if (!$prev_price) {
                            $prev_price = Price::whereNull('shop_id')
                                ->where('effective_at', '<', $changeTimestamp)
                                ->orderBy('effective_at', 'desc')
                                ->first();
                        }

                        PriceAuditLog::create([
                            'user_id' => Auth::id(),
                            'shop_id' => $dailyReport->shop_id,
                            'action' => 'CREATE_FROM_REPORT',
                            'harga_beli_lama' => $prev_price ? $prev_price->harga_beli : null,
                            'harga_jual_lama' => $prev_price ? $prev_price->harga_jual : null,
                            'harga_beli_baru' => $change['harga_beli'],
                            'harga_jual_baru' => $change['harga_jual']
                        ]);
                    }

                    $dailyReport->periods()->create([
                        'price_id' => $lastPriceId,
                        'totalisator_awal' => $currentAwal,
                        'totalisator_akhir' => $changeTotalisator,
                    ]);

                    $currentAwal = $changeTotalisator;
                    $lastPriceId = $price->id;
                }
            }

            if ($totalisator_akhir > $currentAwal) {
                $dailyReport->periods()->create([
                    'price_id' => $lastPriceId,
                    'totalisator_awal' => $currentAwal,
                    'totalisator_akhir' => $totalisator_akhir,
                ]);
            }
        }
    }
}
