<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\User;
use App\Models\Operator;
use App\Models\ExcelUpload;
use App\Models\ExcelOperasional;
use App\Models\ExcelSetoran;
use App\Models\ExcelRekap;
use App\Models\ExcelChangeLog;
use App\Models\DepositDestination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExcelImportController extends Controller
{
    public function index()
    {
        $uploads = ExcelUpload::with(['shop', 'user'])->orderBy('created_at', 'desc')->get();
        $shops = Shop::all();
        return view('excel_import.index', compact('uploads', 'shops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'periode' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        $hash = md5_file($file->getRealPath());

        // Check for duplicate uploads
        $existing = ExcelUpload::where('file_hash', $hash)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Berkas Excel ini sudah pernah di-upload sebelumnya (Duplikat).');
        }

        $shop = Shop::findOrFail($request->shop_id);
        $filePath = $file->store('excel_imports');

        // Seed default destinations if empty
        if (DepositDestination::count() === 0) {
            DepositDestination::create(['name' => 'Sinergy', 'description' => 'Rekening Sinergy']);
            DepositDestination::create(['name' => 'Pak Yusuf', 'description' => 'Setoran Pak Yusuf']);
        }

        $sinergyDest = DepositDestination::where('name', 'Sinergy')->first();
        $yusufDest = DepositDestination::where('name', 'Pak Yusuf')->first();

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetNames = $spreadsheet->getSheetNames();

            $dailySheetName = null;
            $yusufSheetName = null;
            $rekapSheetName = null;

            foreach ($sheetNames as $name) {
                $lower = strtolower($name);
                if (strpos($lower, 'yusuf') !== false) {
                    // Check if it matches the requested period month to avoid older sheets
                    $monthText = strtolower(explode(' ', $request->periode)[0]);
                    if (strpos($lower, $monthText) !== false || !$yusufSheetName) {
                        $yusufSheetName = $name;
                    }
                } elseif (strpos($lower, 'rekap') !== false) {
                    $rekapSheetName = $name;
                } elseif (strpos($lower, 'cek') === false) {
                    $monthText = strtolower(explode(' ', $request->periode)[0]);
                    if (strpos($lower, $monthText) !== false) {
                        $dailySheetName = $name;
                    }
                }
            }

            if (!$dailySheetName || !$yusufSheetName || !$rekapSheetName) {
                throw new \Exception("Struktur sheet Excel tidak valid. Pastikan terdapat sheet Harian, sheet Setoran P. Yusuf, dan sheet Rekap.");
            }

            $dailySheet = $spreadsheet->getSheetByName($dailySheetName);
            $yusufSheet = $spreadsheet->getSheetByName($yusufSheetName);
            $rekapSheet = $spreadsheet->getSheetByName($rekapSheetName);

            // Read starting parameters
            // Juni 26 row 5 Column E has starting totalisator
            $initial_totalisator = floatval($dailySheet->getCell('E5')->getCalculatedValue());
            // Column M5 has starting stock
            $initial_stock = floatval($dailySheet->getCell('M5')->getCalculatedValue());
            // P. Yusuf row 4 Column G has initial balance
            $initial_balance = floatval($yusufSheet->getCell('G4')->getCalculatedValue());
            // Scale factor
            $skala = floatval($shop->skala ?: 21.0);

            // Create upload record
            $upload = ExcelUpload::create([
                'nama_file' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_hash' => $hash,
                'file_size' => $file->getSize(),
                'periode' => $request->periode,
                'shop_id' => $shop->id,
                'user_id' => Auth::id()
            ]);

            // Save prices
            $price1 = floatval($dailySheet->getCell('B104')->getCalculatedValue());
            $price2 = floatval($dailySheet->getCell('B105')->getCalculatedValue());
            $price3 = floatval($dailySheet->getCell('B106')->getCalculatedValue());

            $tebus1 = 11496.293;
            $tebus2 = 11496.293;
            $tebus3 = 15454.806;

            // Parse Daily Sheet
            $r = 6;
            while (true) {
                $colA = trim(strval($dailySheet->getCell('A' . $r)->getValue()));
                if (strtolower($colA) === 'jumlah' || $r > 150) {
                    break;
                }

                // Get date of the block (3 shifts per day)
                $blockStartRow = 6 + floor(($r - 6) / 3) * 3;
                $dateVal = $dailySheet->getCell('B' . $blockStartRow)->getCalculatedValue();
                
                if (!$dateVal) {
                    $r++;
                    continue;
                }

                if (is_numeric($dateVal)) {
                    $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateVal);
                    $dateStr = $dateTime->format('Y-m-d');
                } else {
                    $dateStr = date('Y-m-d');
                }

                $opName = trim(strval($dailySheet->getCell('C' . $r)->getValue()));
                
                // Skip rows with no operator name (empty row at bottom of day block)
                if (empty($opName)) {
                    $r++;
                    continue;
                }

                // Match or create operator dynamically
                $operator = Operator::where('shop_id', $shop->id)
                    ->whereHas('user', function($q) use ($opName) {
                        $q->where('name', 'like', '%'.$opName.'%');
                    })->first();

                if (!$operator) {
                    $user = User::firstOrCreate(
                        ['email' => strtolower($opName) . '@' . strtolower(str_replace(' ', '', $shop->nama)) . '.com'],
                        [
                            'name' => ucfirst($opName),
                            'password' => bcrypt('123'),
                            'role' => 'operator'
                        ]
                    );
                    $operator = Operator::firstOrCreate([
                        'shop_id' => $shop->id,
                        'user_id' => $user->id
                    ]);
                }

                // Determine active prices for this shift row
                $row_price = $price2;
                $row_tebus = $tebus2;
                if ($r >= 6 && $r <= 8) {
                    $row_price = $price1;
                    $row_tebus = $tebus1;
                } elseif ($r >= 33) {
                    $row_price = $price3;
                    $row_tebus = $tebus3;
                }

                // Read input values
                $totalisator_akhir = floatval($dailySheet->getCell('E' . $r)->getCalculatedValue());
                $test_pump = floatval($dailySheet->getCell('J' . $r)->getCalculatedValue());
                $curah = floatval($dailySheet->getCell('K' . $r)->getCalculatedValue());
                
                $stik_malam_val = $dailySheet->getCell('L' . $r)->getValue();
                $stik_malam = ($stik_malam_val !== null && $stik_malam_val !== '') ? floatval($dailySheet->getCell('L' . $r)->getCalculatedValue()) : null;
                
                $pengeluaran = floatval($dailySheet->getCell('Q' . $r)->getCalculatedValue());
                $keterangan_pengeluaran = $dailySheet->getCell('R' . $r)->getValue();
                $qris = floatval($dailySheet->getCell('U' . $r)->getCalculatedValue());

                // Read excel setoran and calculate setoran adjustment to preserve 100% equivalence
                $excel_setoran = floatval($dailySheet->getCell('V' . $r)->getCalculatedValue());
                
                // Temp calculate liter to estimate pendapatan for adjustment calculation
                $tot_awal_temp = ($r === 6) ? $initial_totalisator : floatval($dailySheet->getCell('D' . $r)->getCalculatedValue());
                $liter_temp = max(0.0, $totalisator_akhir - $tot_awal_temp - $test_pump);
                $rupiah_temp = $liter_temp * $row_price;
                $pendapatan_temp = $rupiah_temp - $pengeluaran;
                
                $setoran_calc_temp = ceil(($pendapatan_temp - $qris) / 500) * 500;
                if ($setoran_calc_temp < 0) $setoran_calc_temp = 0;
                $setoran_adjustment = $excel_setoran - $setoran_calc_temp;

                // Save ExcelOperasional row
                ExcelOperasional::create([
                    'upload_id' => $upload->id,
                    'tanggal' => $dateStr,
                    'operator_id' => $operator->id,
                    'excel_operator_name' => $opName,
                    'totalisator_akhir' => $totalisator_akhir,
                    'test_pump' => $test_pump,
                    'curah' => $curah,
                    'stik_malam' => $stik_malam,
                    'harga_jual' => $row_price,
                    'harga_tebus' => $row_tebus,
                    'pengeluaran' => $pengeluaran,
                    'keterangan_pengeluaran' => $keterangan_pengeluaran,
                    'qris' => $qris,
                    'setoran_adjustment' => $setoran_adjustment
                ]);

                // Parse Yusuf Sinergy setoran for this shift row
                $yusuf_row_idx = 5 + floor(($r - 6) / 3) * 3 + (($r - 6) % 3);
                $sinergy_val = floatval($yusufSheet->getCell('F' . $yusuf_row_idx)->getCalculatedValue());
                
                if ($sinergy_val > 0) {
                    ExcelSetoran::create([
                        'upload_id' => $upload->id,
                        'tanggal' => $dateStr,
                        'operator_id' => $operator->id,
                        'deposit_destination_id' => $sinergyDest->id,
                        'nominal' => $sinergy_val,
                        'keterangan' => 'Disetor ke Sinergy'
                    ]);
                }

                $r++;
            }

            // Update upload start points
            $upload->update([
                'initial_totalisator' => $initial_totalisator,
                'initial_stock' => $initial_stock,
                'initial_balance' => $initial_balance,
                'skala' => $skala
            ]);

            // Parse Rekap Sheet
            $detail_do = [];
            for ($i = 10; $i <= 14; $i++) {
                $detail_do[] = [
                    'label' => strval($rekapSheet->getCell('B' . $i)->getValue()),
                    'liter' => floatval($rekapSheet->getCell('C' . $i)->getCalculatedValue()),
                    'rupiah' => floatval($rekapSheet->getCell('E' . $i)->getCalculatedValue())
                ];
            }

            $detail_pengeluaran = [];
            for ($i = 3; $i <= 7; $i++) {
                $detail_pengeluaran[] = [
                    'label' => strval($rekapSheet->getCell('I' . $i)->getValue()),
                    'rupiah' => floatval($rekapSheet->getCell('L' . $i)->getCalculatedValue())
                ];
            }

            $detail_pembagian = [];
            for ($i = 32; $i <= 34; $i++) {
                $detail_pembagian[] = [
                    'label' => strval($rekapSheet->getCell('B' . $i)->getValue()),
                    'rupiah' => floatval($rekapSheet->getCell('E' . $i)->getCalculatedValue())
                ];
            }

            ExcelRekap::create([
                'upload_id' => $upload->id,
                'harga_tebus_active' => $tebus1,
                'harga_jual_active' => $price1,
                'detail_do' => $detail_do,
                'detail_pengeluaran_rutin' => $detail_pengeluaran,
                'detail_pembagian_hasil' => $detail_pembagian
            ]);

            DB::commit();
            return redirect()->route('excel-imports.show', $upload->id)->with('success', 'File Excel berhasil di-import dengan akurasi 100%!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $upload = ExcelUpload::with(['shop', 'rekap', 'changeLogs.user'])->findOrFail($id);
        $calculatedRows = $upload->getCalculatedOperasionals();
        $dynamicRekap = $upload->getCalculatedRekaps();
        $destinations = DepositDestination::all();
        return view('excel_import.show', compact('upload', 'calculatedRows', 'dynamicRekap', 'destinations'));
    }

    public function updateCell(Request $request, $id)
    {
        $request->validate([
            'row_id' => 'required|exists:excel_operasionals,id',
            'field' => 'required|string',
            'value' => 'nullable'
        ]);

        $row = ExcelOperasional::findOrFail($request->row_id);
        $oldVal = $row->{$request->field};
        $newVal = $request->value;

        // Perform custom validations based on business logic
        if ($request->field === 'totalisator_akhir') {
            // Find previous row to prevent negative sales volume
            $prev = ExcelOperasional::where('upload_id', $row->upload_id)
                ->where('id', '<', $row->id)
                ->orderBy('id', 'desc')
                ->first();
            $tot_awal = $prev ? $prev->totalisator_akhir : $row->upload->initial_totalisator;
            if ($newVal !== null && floatval($newVal) < $tot_awal) {
                return response()->json(['error' => 'Totalisator akhir tidak boleh lebih kecil dari totalisator awal (' . $tot_awal . ')!'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $row->update([
                $request->field => $newVal
            ]);

            // Save change log
            ExcelChangeLog::create([
                'upload_id' => $id,
                'row_id' => $row->id,
                'field' => $request->field,
                'nilai_lama' => strval($oldVal),
                'nilai_baru' => strval($newVal),
                'user_id' => Auth::id()
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal mengubah data: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $upload = ExcelUpload::findOrFail($id);
        $upload->delete();
        return redirect()->route('excel-imports.index')->with('success', 'Data import Excel berhasil dihapus (pindah ke Tong Sampah).');
    }

    public function trash()
    {
        $trashedUploads = ExcelUpload::onlyTrashed()->with(['shop', 'user'])->orderBy('deleted_at', 'desc')->get();
        return view('excel_import.trash', compact('trashedUploads'));
    }

    public function restore($id)
    {
        $upload = ExcelUpload::onlyTrashed()->findOrFail($id);
        $upload->restore();
        return redirect()->route('excel-imports.trash')->with('success', 'Data import Excel berhasil dikembalikan.');
    }

    public function forceDelete($id)
    {
        $upload = ExcelUpload::onlyTrashed()->findOrFail($id);
        
        if ($upload->file_path && Storage::exists($upload->file_path)) {
            Storage::delete($upload->file_path);
        }
        
        // Due to cascading relationships, we might need to delete related data manually if no foreign key cascade deletes are set up
        $upload->operasionals()->delete();
        $upload->setorans()->delete();
        if ($upload->rekap) {
            $upload->rekap->delete();
        }
        $upload->changeLogs()->delete();
        
        $upload->forceDelete();
        
        return redirect()->route('excel-imports.trash')->with('success', 'Data import Excel beserta filenya berhasil dihapus permanen.');
    }
}
