<?php

namespace App\Http\Controllers;

use App\Models\FactoryArea;
use App\Models\MachineType;
use App\Models\ProductionBox;
use Illuminate\Http\Request;

class TimterController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $areaId = $request->input('area_id');
        $machineTypeId = $request->input('machine_type_id');

        $query = ProductionBox::with(['machine.machineType', 'shipment.orderStyle.order', 'factoryArea'])
            ->whereDate('production_date', $date);

        if ($areaId) {
            $query->where('factory_area_id', $areaId);
        }

        if ($machineTypeId) {
            $query->whereHas('machine', function ($q) use ($machineTypeId) {
                $q->where('machine_type_id', $machineTypeId);
            });
        }

        $records = $query->orderBy('no_box')->get();

        // Agregat Timter per Mesin & Model
        $timterSummary = $records->groupBy(function ($item) {
            return ($item->machine?->machine_number ?? '-') . '|' . ($item->shipment?->orderStyle?->style_code ?? '-');
        })->map(function ($group) {
            $first = $group->first();
            $shiftA = $group->sum('shift_a');
            $shiftB = $group->sum('shift_b');
            $shiftC = $group->sum('shift_c');
            $totalPcs = $group->sum('good_pcs');
            $totalWeight = $group->sum('weight_kg');

            return (object) [
                'machine_number' => $first->machine?->machine_number ?? '-',
                'machine_type' => $first->machine?->machineType?->name ?? '-',
                'style_code' => $first->shipment?->orderStyle?->style_code ?? '-',
                'po_number' => $first->shipment?->orderStyle?->order?->po_number ?? '-',
                'shift_a' => $shiftA,
                'shift_b' => $shiftB,
                'shift_c' => $shiftC,
                'total_pcs' => $totalPcs,
                'total_dz' => round($totalPcs / 24, 2),
                'total_weight_kg' => round($totalWeight, 3),
                'avg_gram_per_pair' => $totalPcs > 0 ? round(($totalWeight * 1000) / $totalPcs, 1) : 0,
            ];
        });

        $areas = FactoryArea::orderBy('area_name')->get();
        $machineTypes = MachineType::orderBy('brand')->get();

        $stats = [
            'date' => $date,
            'total_boxes' => $records->count(),
            'total_pcs' => $records->sum('good_pcs'),
            'total_weight_kg' => round($records->sum('weight_kg'), 2),
        ];

        return view('production.timter.index', compact('timterSummary', 'areas', 'machineTypes', 'stats', 'date', 'areaId', 'machineTypeId'));
    }
}
