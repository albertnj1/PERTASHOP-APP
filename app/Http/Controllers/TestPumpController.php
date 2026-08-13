<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Operator;
use App\Models\TestPump;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class TestPumpController extends Controller
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

            $data = TestPump::with(['operator.user'])->where('shop_id', $shop_id)->latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($data) {
                    if (Auth::user()->role == 'investor') return '';

                    if (Auth::user()->role == 'operator') {
                        $lastRow = $data->first(); // Mendapatkan data terakhir dari koleksi
                        $button = '';

                        if ($row->id === $lastRow->id && $row->operator_id === Auth::user()->operator->id) { // Menambahkan tombol hanya pada data terakhir
                            $button = '<a href="' . route('test-pumps.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                            $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } else {
                        $button = '<a href="' . route('test-pumps.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();

        return view('test_pump.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shop = Auth::user()->operator->shop;

        return view('test_pump.create', compact('shop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validatedData = $request->validate([
            'totalisator_awal' => 'required|numeric',
            'totalisator_akhir' => 'required|numeric',
        ], $customMessages);

        $validatedData['operator_id'] = Auth::user()->operator->id;
        $validatedData['shop_id'] = Auth::user()->operator->shop->id;
        $validatedData['created_at'] = Carbon::now()->format('Y-m-d H:i');

        TestPump::create($validatedData);
        return to_route('test-pumps.index')->with('success', 'Data test pump berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TestPump $testPump)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TestPump $testPump)
    {
        return view('test_pump.edit', compact('testPump'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestPump $testPump)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validatedData = $request->validate([
            'totalisator_awal' => 'required|numeric',
            'totalisator_akhir' => 'required|numeric',
        ], $customMessages);


        $testPump->update($validatedData);
        return to_route('test-pumps.index')->with('success', 'Data test pump berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestPump $testPump)
    {
        $testPump->delete();

        return response()->json([
            'message' => 'Data percobaan telah dihapus.'
        ]);
    }
}
