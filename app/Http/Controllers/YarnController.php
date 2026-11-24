<?php

namespace App\Http\Controllers;

use App\Models\FactoryArea;
use App\Models\YarnStockBalance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YarnController extends Controller
{
    public function index(Request $request): View
    {
        $query = YarnStockBalance::with('factoryArea');

        if ($request->filled('area_id')) {
            $query->where('factory_area_id', $request->input('area_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('yarn_code', 'like', "%{$search}%")
                  ->orWhere('yarn_name', 'like', "%{$search}%")
                  ->orWhere('color_name', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%");
            });
        }

        $yarnStocks = $query->orderBy('yarn_code')->paginate(20)->withQueryString();

        $totalLots = YarnStockBalance::count();
        $totalCones = (int) YarnStockBalance::sum('qty_cones');
        $totalWeightKg = (float) YarnStockBalance::sum('weight_kg');

        $factoryAreas = FactoryArea::orderBy('area_name')->get();

        return view('yarn.index', compact(
            'yarnStocks',
            'totalLots',
            'totalCones',
            'totalWeightKg',
            'factoryAreas'
        ));
    }
}
