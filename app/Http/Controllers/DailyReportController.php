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
use App\Models\ShiftSchedule;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

use App\Models\BackdateExcelFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyReportController extends Controller
{
    /**
     * Resolve active shop, startDate, and endDate for index and exports.
     */
    protected function resolveShopAndDates(Request $request): array
    {
        $user = Auth::user();
        if ($user->role == 'admin') {
            $shop_id = $user->admin?->shop_id;
        } elseif ($user->role == 'operator') {
            $shop_id = $user->operator?->shop_id;
        } elseif ($user->role == 'investor') {
            $investor_shops = $user->investor?->shops->pluck('id')->toArray() ?? [];
            $shop_id = $request->input('shop_id');
            if (!$shop_id || !in_array($shop_id, $investor_shops)) {
                $shop_id = reset($investor_shops) ?: Shop::first()?->id;
            }
        } else {
            // Super Admin / Manager: default to first existing shop in DB
            $defaultShop = Shop::first();
            $shop_id = $request->input('shop_id', $defaultShop?->id);
        }

        $shop = Shop::find($shop_id) ?? Shop::first();

        // Default rentang tanggal: Bulan Ini (awal bulan s/d hari ini)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        return [$shop, $startDate, $endDate];
    }

    /**
     * Calculate summary aggregates for a collection of reports.
     */
    protected function calculateTotals($reports): array
    {
        return [
            'penerimaan'       => (float) $reports->sum('penerimaan'),
            'volume_penjualan' => (float) $reports->sum('volume_penjualan'),
            'rupiah_penjualan' => (float) $reports->sum('rupiah_penjualan'),
            'losses_gain'      => (float) $reports->sum('losses_gain'),
            'pengeluaran'      => (float) $reports->sum('pengeluaran'),
            'pendapatan'       => (float) $reports->sum('pendapatan'),
            'disetorkan'       => (float) $reports->sum('disetorkan'),
            'running_balance'  => (float) ($reports->last()?->running_balance_setoran ?? 0),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        [$shop, $startDate, $endDate] = $this->resolveShopAndDates($request);

        if ($request->ajax()) {
            // Kolom tanggal operasional laporan (created_at di DB menyimpan tanggal operasional laporan)
            $dateColumn = \Illuminate\Support\Facades\Schema::hasColumn('daily_reports', 'tanggal') ? 'tanggal' : 'created_at';

            $query = DailyReport::with(['operator.user', 'spendings', 'incomings', 'testPumps', 'backdateFile'])
                ->where('shop_id', $shop?->id);

            // Filter rentang tanggal operasional laporan
            if ($startDate && $endDate) {
                $query->whereDate($dateColumn, '>=', $startDate)
                      ->whereDate($dateColumn, '<=', $endDate);
            } elseif ($startDate) {
                $query->whereDate($dateColumn, '>=', $startDate);
            } elseif ($endDate) {
                $query->whereDate($dateColumn, '<=', $endDate);
            }

            // PENTING: Data Backdate Arsip (sumber_data = 'import_excel_arsip') TIDAK LAGI DIBLOKIR.
            // Seluruh data (manual maupun upload arsip backdate) akan tampil sesuai periode tanggal operasionalnya.
            $data = $query->orderBy($dateColumn, 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($data) {
                    $button = '';

                    // Jika merupakan data hasil upload Backdate Arsip
                    if ($row->sumber_data === 'import_excel_arsip') {
                        $button .= '<span class="badge badge-info mr-1" title="Data dari Import Arsip Backdate" style="font-size: 11px; padding: 4px 8px;"><i class="fa fa-history mr-1"></i>Backdate</span>';

                        $backdateFile = $row->backdateFile;
                        if (!$backdateFile && !empty($row->sumber_file_excel)) {
                            $backdateFile = BackdateExcelFile::where('original_filename', $row->sumber_file_excel)
                                ->orWhere('file_path', 'like', '%' . $row->sumber_file_excel . '%')
                                ->first();
                        }

                        if ($backdateFile) {
                            $button .= '<a href="' . route('backdate-excel-files.download', $backdateFile->id) . '" class="btn btn-sm btn-outline-success mr-1" title="Unduh File Acuan Excel (' . e($backdateFile->original_filename) . ')"><i class="fa fa-file-excel"></i></a>';
                            $button .= '<a href="' . route('backdate-excel-files.download-pdf', $backdateFile->id) . '" target="_blank" class="btn btn-sm btn-outline-danger mr-1" title="Preview / Unduh PDF Acuan"><i class="fa fa-file-pdf"></i></a>';
                        } elseif (!empty($row->sumber_file_excel)) {
                            $button .= '<span class="badge badge-light border text-muted mr-1" title="File Acuan: ' . e($row->sumber_file_excel) . '"><i class="fa fa-file-excel text-success mr-1"></i>' . e(\Illuminate\Support\Str::limit($row->sumber_file_excel, 15)) . '</span>';
                        }
                    }

                    if (Auth::user()->role == 'operator') {
                        $lastRow = $data->first();
                        if ($row->sumber_data !== 'import_excel_arsip' && $row->id === $lastRow?->id && $row->operator_id === Auth::user()->operator?->id) {
                            $button .= '<a href="' . route('daily-reports.edit', $row->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fa fa-edit"></i></a>';
                            $button .= '<button class="btn btn-sm btn-danger btn-delete" title="Hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } elseif (Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin') {
                        if ($row->sumber_data !== 'import_excel_arsip') {
                            $button .= '<a href="' . route('daily-reports.edit', $row->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fa fa-edit"></i></a>';
                        }
                        $button .= '<button class="btn btn-sm btn-danger btn-delete" title="Hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $button ?: '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor?->shops ?? collect();
        }

        $selectedShopId = $shop?->id;

        return view('daily_report.index', compact('shops', 'shop', 'selectedShopId', 'startDate', 'endDate'));
    }

    /**
     * Export Daily Reports to formatted Excel (.xlsx) using PhpSpreadsheet.
     */
    public function exportExcel(Request $request)
    {
        [$shop, $startDate, $endDate] = $this->resolveShopAndDates($request);

        $dateColumn = \Illuminate\Support\Facades\Schema::hasColumn('daily_reports', 'tanggal') ? 'tanggal' : 'created_at';

        $query = DailyReport::with(['operator.user', 'spendings', 'incomings', 'testPumps'])
            ->where('shop_id', $shop?->id);

        if ($startDate && $endDate) {
            $query->whereDate($dateColumn, '>=', $startDate)
                  ->whereDate($dateColumn, '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        $reports = $query->orderBy($dateColumn, 'asc')->get();
        $totals = $this->calculateTotals($reports);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Harian');

        // Document Header Styling
        $sheet->mergeCells('A1:N1');
        $sheet->setCellValue('A1', 'LAPORAN OPERASIONAL HARIAN PERTASHOP');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('00529C'));

        $sheet->mergeCells('A2:N2');
        $shopInfo = ($shop ? $shop->nama . " (Kode: {$shop->kode})" : 'Semua Cabang') . ' — ' . ($shop?->alamat ?? '');
        $sheet->setCellValue('A2', $shopInfo);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));

        $sheet->mergeCells('A3:N3');
        $periodeText = "Periode: " . Carbon::parse($startDate)->translatedFormat('d M Y') . " s/d " . Carbon::parse($endDate)->translatedFormat('d M Y') . " | Total: " . count($reports) . " Laporan";
        $sheet->setCellValue('A3', $periodeText);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        // Table Column Headers (Row 5)
        $headers = [
            'A5' => 'No',
            'B5' => 'Tanggal',
            'C5' => 'Sumber Data',
            'D5' => 'Operator',
            'E5' => 'Penerimaan (L)',
            'F5' => 'Volume Jual (L)',
            'G5' => 'Rupiah Penjualan',
            'H5' => 'Stok Akhir (L)',
            'I5' => 'Losses / Gain (L)',
            'J5' => 'Pengeluaran',
            'K5' => 'Pendapatan',
            'L5' => 'Disetorkan',
            'M5' => 'Saldo Belum Setor',
            'N5' => 'Status',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00529C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '003366']]],
        ];
        $sheet->getStyle('A5:N5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Populate Data Rows
        $rowNum = 6;
        foreach ($reports as $idx => $r) {
            $isBackdate = ($r->sumber_data === 'import_excel_arsip');
            $opName = $r->operator?->user?->name ?? ($isBackdate ? 'Arsip / Sistem' : '-');
            $sumberText = $isBackdate ? 'Backdate Arsip' : 'Input Manual';

            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValue("B{$rowNum}", Carbon::parse($r->created_at)->format('d/m/Y'));
            $sheet->setCellValue("C{$rowNum}", $sumberText);
            $sheet->setCellValue("D{$rowNum}", $opName);
            $sheet->setCellValue("E{$rowNum}", (float) $r->penerimaan);
            $sheet->setCellValue("F{$rowNum}", (float) $r->volume_penjualan);
            $sheet->setCellValue("G{$rowNum}", (float) $r->rupiah_penjualan);
            $sheet->setCellValue("H{$rowNum}", (float) $r->stok_akhir_aktual);
            $sheet->setCellValue("I{$rowNum}", (float) $r->losses_gain);
            $sheet->setCellValue("J{$rowNum}", (float) $r->pengeluaran);
            $sheet->setCellValue("K{$rowNum}", (float) $r->pendapatan);
            $sheet->setCellValue("L{$rowNum}", (float) $r->disetorkan);
            $sheet->setCellValue("M{$rowNum}", (float) $r->running_balance_setoran);
            $sheet->setCellValue("N{$rowNum}", ucfirst($r->status_lifecycle ?? 'draft'));

            // Number formats
            $sheet->getStyle("E{$rowNum}:F{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("H{$rowNum}:I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("J{$rowNum}:M{$rowNum}")->getNumberFormat()->setFormatCode('"Rp "#,##0');

            // Alignment
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}:M{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Row zebra striping
            if ($rowNum % 2 == 1) {
                $sheet->getStyle("A{$rowNum}:N{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $rowNum++;
        }

        // Summary Total Row
        if (count($reports) > 0) {
            $sheet->mergeCells("A{$rowNum}:D{$rowNum}");
            $sheet->setCellValue("A{$rowNum}", 'TOTAL KESELURUHAN');
            $sheet->setCellValue("E{$rowNum}", $totals['penerimaan']);
            $sheet->setCellValue("F{$rowNum}", $totals['volume_penjualan']);
            $sheet->setCellValue("G{$rowNum}", $totals['rupiah_penjualan']);
            $sheet->setCellValue("H{$rowNum}", '-');
            $sheet->setCellValue("I{$rowNum}", $totals['losses_gain']);
            $sheet->setCellValue("J{$rowNum}", $totals['pengeluaran']);
            $sheet->setCellValue("K{$rowNum}", $totals['pendapatan']);
            $sheet->setCellValue("L{$rowNum}", $totals['disetorkan']);
            $sheet->setCellValue("M{$rowNum}", $totals['running_balance']);
            $sheet->setCellValue("N{$rowNum}", '-');

            $sheet->getStyle("E{$rowNum}:F{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("J{$rowNum}:M{$rowNum}")->getNumberFormat()->setFormatCode('"Rp "#,##0');

            $totalStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '64748B']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '64748B']],
                ],
            ];
            $sheet->getStyle("A{$rowNum}:N{$rowNum}")->applyFromArray($totalStyle);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}:M{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowNum)->setRowHeight(22);
        }

        // Apply border to entire table
        $lastRow = count($reports) > 0 ? $rowNum : 5;
        $sheet->getStyle("A5:N{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Auto-fit column widths
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $shopSlug = \Illuminate\Support\Str::slug($shop?->nama ?? 'pertashop');
        $fileName = "Laporan_Harian_{$shopSlug}_{$startDate}_sd_{$endDate}.xlsx";

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Daily Reports to PDF in landscape format using DomPDF.
     */
    public function exportPdf(Request $request)
    {
        [$shop, $startDate, $endDate] = $this->resolveShopAndDates($request);

        $dateColumn = \Illuminate\Support\Facades\Schema::hasColumn('daily_reports', 'tanggal') ? 'tanggal' : 'created_at';

        $query = DailyReport::with(['operator.user', 'spendings', 'incomings', 'testPumps'])
            ->where('shop_id', $shop?->id);

        if ($startDate && $endDate) {
            $query->whereDate($dateColumn, '>=', $startDate)
                  ->whereDate($dateColumn, '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        $reports = $query->orderBy($dateColumn, 'asc')->get();
        $totals = $this->calculateTotals($reports);

        $pdf = Pdf::loadView('pdf.daily_reports_export', compact('reports', 'shop', 'startDate', 'endDate', 'totals'))
            ->setPaper('a4', 'landscape');

        $shopSlug = \Illuminate\Support\Str::slug($shop?->nama ?? 'pertashop');
        $fileName = "Laporan_Harian_{$shopSlug}_{$startDate}_sd_{$endDate}.pdf";

        return $pdf->download($fileName);
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
            ->where(function($q) {
                $q->whereNull('effective_at')->orWhere('effective_at', '<=', Carbon::now());
            })
            ->orderByRaw('COALESCE(effective_at, created_at) DESC')
            ->orderBy('id', 'desc')
            ->first();

        if (!$latest_price) {
            $latest_price = Price::whereNull('shop_id')
                ->where(function($q) {
                    $q->whereNull('effective_at')->orWhere('effective_at', '<=', Carbon::now());
                })
                ->orderByRaw('COALESCE(effective_at, created_at) DESC')
                ->orderBy('id', 'desc')
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

        $kolektans = Kolektan::where('shop_id', $shop_id)->orWhereNull('shop_id')->get();

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
            'foto_stik' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
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

        if ($request->hasFile('foto_stik')) {
            $validated['foto_stik'] = $request->file('foto_stik')->store('foto_stik', 'public');
        }

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

        // Auto-create Incomings if Operator checked pending SOs
        if ($request->has('received_purchases_ids') && is_array($request->received_purchases_ids)) {
            $received_volumes = $request->input('received_purchases_volumes', []);
            foreach ($request->received_purchases_ids as $purchase_id) {
                $purchase = \App\Models\Purchase::find($purchase_id);
                $vol = isset($received_volumes[$purchase_id]) ? floatval($received_volumes[$purchase_id]) : 0;
                if ($purchase && $purchase->sisa > 0 && $vol > 0) {
                    if ($vol > $purchase->sisa) $vol = $purchase->sisa; // Prevent over-receive
                    Incoming::create([
                        'daily_report_id' => $dailyReport->id,
                        'shop_id' => $shop_id,
                        'operator_id' => $operator_id,
                        'purchase_id' => $purchase_id,
                        'incoming_date' => Carbon::parse($reportDate)->format('Y-m-d'),
                        'sopir' => '-',
                        'no_polisi' => '-',
                        'volume' => $vol,
                        'stik_awal' => $request->input('stik_awal'),
                        'stik_akhir' => $request->input('stik_akhir'),
                        'penerimaan_real' => $vol,
                        'created_at' => Carbon::parse($reportDate)->format('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        // Sync spendings
        $spendingsInput = $request->input('spendings', []);

        // Compatibility for old cached forms that sent 3-9 instead of 1-7
        if (isset($spendingsInput[8]) || isset($spendingsInput[9])) {
            $mappedSpendings = [];
            if (isset($spendingsInput[3])) $mappedSpendings[1] = $spendingsInput[3];
            if (isset($spendingsInput[4])) $mappedSpendings[2] = $spendingsInput[4];
            if (isset($spendingsInput[5])) $mappedSpendings[3] = $spendingsInput[5];
            if (isset($spendingsInput[6])) $mappedSpendings[4] = $spendingsInput[6];
            if (isset($spendingsInput[7])) $mappedSpendings[5] = $spendingsInput[7];
            if (isset($spendingsInput[8])) $mappedSpendings[6] = $spendingsInput[8];
            if (isset($spendingsInput[9])) $mappedSpendings[7] = $spendingsInput[9];
            $spendingsInput = $mappedSpendings;
        }

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

        // Auto-mark kehadiran: cari ShiftSchedule operator di tanggal laporan ini
        // Jika ada jadwal yang di-assign ke operator ini, ubah statusnya menjadi 'hadir'
        $reportDateOnly = \Carbon\Carbon::parse($reportDate)->toDateString();
        ShiftSchedule::where('operator_id', $operator_id)
            ->where('tanggal', $reportDateOnly)
            ->where('status', 'dijadwalkan')
            ->each(fn ($s) => $s->update(['status' => 'hadir']));

        // Invalidate dashboard cache supaya stok & rekapitulasi langsung ter-update
        $this->invalidateDashboardCache($shop_id);

        return to_route('daily-reports.index')->with('success', 'Data laporan harian berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyReport $dailyReport)
    {
        return redirect()->route('daily-reports.edit', $dailyReport->id);
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

        $kolektans = Kolektan::where('shop_id', $dailyReport->shop_id)->orWhereNull('shop_id')->get();

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

        if ($request->hasFile('foto_stik')) {
            if ($dailyReport->foto_stik) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($dailyReport->foto_stik);
            }
            $validated['foto_stik'] = $request->file('foto_stik')->store('foto_stik', 'public');
        }

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

        // Compatibility for old cached forms that sent 3-9 instead of 1-7
        if (isset($spendingsInput[8]) || isset($spendingsInput[9])) {
            $mappedSpendings = [];
            if (isset($spendingsInput[3])) $mappedSpendings[1] = $spendingsInput[3];
            if (isset($spendingsInput[4])) $mappedSpendings[2] = $spendingsInput[4];
            if (isset($spendingsInput[5])) $mappedSpendings[3] = $spendingsInput[5];
            if (isset($spendingsInput[6])) $mappedSpendings[4] = $spendingsInput[6];
            if (isset($spendingsInput[7])) $mappedSpendings[5] = $spendingsInput[7];
            if (isset($spendingsInput[8])) $mappedSpendings[6] = $spendingsInput[8];
            if (isset($spendingsInput[9])) $mappedSpendings[7] = $spendingsInput[9];
            $spendingsInput = $mappedSpendings;
        }

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

        // Invalidate dashboard cache
        $this->invalidateDashboardCache($shop_id);

        // Audit log
        AuditLog::log('UPDATE', DailyReport::class, $dailyReport->id, 'Pengubahan Laporan Harian #' . $dailyReport->id);

        return to_route('daily-reports.index')->with('success', 'Data laporan harian berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReport $dailyReport)
    {
        $shopId = $dailyReport->shop_id;
        $reportId = $dailyReport->id;
        $dailyReport->delete();
        $this->invalidateDashboardCache($shopId);

        // Audit log
        AuditLog::log('DELETE', DailyReport::class, $reportId, 'Penghapusan Laporan Harian #' . $reportId);

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

    /**
     * Invalidate semua cache dashboard terkait shop tertentu.
     * Dipanggil setelah setiap store/update/destroy agar dashboard tidak stale.
     */
    private function invalidateDashboardCache(int $shopId): void
    {
        foreach (['month', 'week', 'day'] as $filter) {
            Cache::forget('dashboard_data_' . $shopId . '_' . $filter);
            Cache::forget('dashboard_data_all_' . $filter);
        }
    }
}
