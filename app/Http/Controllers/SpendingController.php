<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Spending;
use App\Models\SpendingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SpendingController extends Controller
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

            $spendings = Spending::with(['category', 'operator.user'])->where('shop_id', $shop_id)
                ->latest()
                ->get();

            if (Auth::user()->role == 'operator') {
                $spendings = Spending::with(['category', 'operator.user'])->where('shop_id', $shop_id)
                    ->where('operator_id', '!=', null)
                    ->latest()
                    ->get();
            }

            return DataTables::of($spendings)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($spendings) {
                    if (Auth::user()->role == 'operator') {
                        $lastRow = $spendings->first(); // Mendapatkan data terakhir dari koleksi
                        $button = '';

                        if ($row->id === $lastRow->id && $row->operator_id === Auth::user()->operator->id) { // Menambahkan tombol hanya pada data terakhir
                            $button = '<button class="btn btn-sm btn-info btn-edit" title="edit" data-id="' . $row->id . '"><i class="fa fa-edit"></i></button>';
                            $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } else {
                        $button = '<button class="btn btn-sm btn-info btn-edit" title="edit" data-id="' . $row->id . '"><i class="fa fa-edit"></i></button>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        // if ($request->ajax()) {
        //     $shop_id = Auth::user()->operator->shop_id;

        //     $spendingsByDate = Spending::with(['shop'])
        //         ->where('shop_id', $shop_id)
        //         ->latest()
        //         ->get()
        //         ->groupBy(function ($item) {
        //             return $item->created_at->format('Y-m-d');
        //         });

        //     $monthsToDisplay = [];

        //     // Create the array of months
        //     $firstData = Spending::with(['shop'])
        //         ->where('shop_id', $shop_id)
        //         ->oldest()
        //         ->get()->first();
        //     $startDate = $firstData ? $firstData->created_at : Carbon::now(); // Start from oldest data
        //     $endDate = now();

        //     while ($startDate <= $endDate) {
        //         $monthsToDisplay[$startDate->format('Y-m-d')] = 0;
        //         $startDate->addDay();
        //     }

        //     $data = [];

        //     foreach ($spendingsByDate as $date => $spendings) {
        //         $totalAmount = $spendings->sum('jumlah');
        //         $monthsToDisplay[$date] = $totalAmount;
        //     }

        //     // Now $monthsToDisplay contains all months from January 2021 with their respective totals (0 or actual total)
        //     foreach ($monthsToDisplay as $date => $total) {
        //         $data[] = [
        //             'created_at' => $date,
        //             'total' => $total,
        //             'shop_id' => $shop_id,
        //         ];
        //     }
        //     return DataTables::of($data)
        //         ->addIndexColumn()
        //         ->addColumn('action', function ($row) {
        //             $button = '<a href="' . route('spendings.edit', ['shop_id' => $row['shop_id'], 'year_month' => $row['created_at']]) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
        //             return $button;
        //         })
        //         ->rawColumns(['action'])
        //         ->make(true);
        // }


        $shops = Shop::all();
        $spendingCategories = SpendingCategory::all();
        if (Auth::user()->role == 'operator') {
            $spendingCategories = SpendingCategory::where('id', '>', 2)->get();
        }
        return view('spending.index', compact('shops', 'spendingCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin->shop_id;
        } elseif (Auth::user()->role == 'operator') {
            $shop_id = Auth::user()->operator->shop_id;
        }

        $validated = $request->validate([
            'created_at' => 'required|date',
            'spending_category_id' => 'required|numeric',
            'jumlah' => 'required|numeric',
            'keterangan' => 'required_if:spending_category_id,99',
        ]);

        Spending::create([
            'shop_id' => $shop_id,
            'operator_id' => Auth::user()->role == 'operator' ? Auth::user()->operator->id : null,
            'created_at' => Auth::user()->role == 'operator' ? Carbon::now()->format('Y-m-d H:i:s') : $validated['created_at'],
            'spending_category_id' => $validated['spending_category_id'],
            'keterangan' => $validated['keterangan'] ?? null,
            'jumlah' => $validated['jumlah'],
        ]);

        return response()->json([
            'message' => 'Data berhasil disimpan.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Spending $spending)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spending $spending)
    {
        //return json response
        return response()->json(compact('spending'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spending $spending)
    {
        //return json response
        $validated = $request->validate([
            'created_at' => 'required|date',
            'spending_category_id' => 'required|numeric',
            'jumlah' => 'required|numeric',
            'keterangan' => 'required_if:spending_category_id,99',
        ]);

        $spending->update([
            'created_at' => Auth::user()->role == 'operator' ? $spending->created_at : $validated['created_at'],
            'spending_category_id' => $validated['spending_category_id'],
            'keterangan' => $validated['keterangan'] ?? null,
            'jumlah' => $validated['jumlah'],
        ]);

        return response()->json([
            'message' => 'Data berhasil diupdate.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spending $spending)
    {
        //delete and return json response
        $spending->delete();
        return response()->json([
            'message' => 'Data berhasil dihapus.',
        ]);
    }
}
