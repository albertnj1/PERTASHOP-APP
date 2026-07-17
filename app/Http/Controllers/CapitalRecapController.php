<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CapitalRecap;
use App\Models\Shop;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CapitalRecapController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::all();
        $query = CapitalRecap::with('shop')->orderBy('shop_id')->orderBy('tahun')->orderBy('bulan');
        
        if ($request->has('shop_id') && $request->shop_id != '') {
            $query->where('shop_id', $request->shop_id);
        }
        
        $recaps = $query->get();
        return view('capital_recaps.index', compact('recaps', 'shops'));
    }

}
