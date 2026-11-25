<?php

namespace App\Http\Controllers;

use App\Models\BoxDefect;
use App\Models\DowntimeReason;
use App\Models\FactoryArea;
use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\ProductionBox;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OeeController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $areaId = $request->input('area_id');

        $areas = FactoryArea::orderBy('area_name')->get();

        $machineQuery = Machine::query();
        if ($areaId) {
            $machineQuery->where('factory_area_id', $areaId);
        }
        $machineCount = max(1, $machineQuery->count());

        // Planned Loading Time (Minutes) = Machine count * 1440 min (24 hours full operation default)
        $totalLoadingMinutes = $machineCount * 1440;

        // Total Downtime Minutes for the date
        $downtimeQuery = MachineDowntime::whereDate('start_time', $date);
        if ($areaId) {
            $downtimeQuery->whereHas('machine', fn ($q) => $q->where('factory_area_id', $areaId));
        }
        $totalDowntimeMinutes = (int) $downtimeQuery->sum('duration_minutes');
        $operatingMinutes = max(0, $totalLoadingMinutes - $totalDowntimeMinutes);

        // Availability = Operating Time / Loading Time
        $availabilityRate = $totalLoadingMinutes > 0
            ? min(100, round(($operatingMinutes / $totalLoadingMinutes) * 100, 2))
            : 100;

        // Total Production & Defect for date
        $prodQuery = ProductionBox::whereDate('production_date', $date);
        if ($areaId) {
            $prodQuery->where('factory_area_id', $areaId);
        }
        $totalProductionPcs = (int) $prodQuery->sum('good_pcs');

        $defectQuery = BoxDefect::whereDate('defect_date', $date);
        if ($areaId) {
            $defectQuery->where('factory_area_id', $areaId);
        }
        $totalDefectPcs = (int) $defectQuery->sum('defect_count_pcs');

        // Quality = Good Output / Total Output
        $totalOutput = $totalProductionPcs + $totalDefectPcs;
        $qualityRate = $totalOutput > 0
            ? min(100, round(($totalProductionPcs / $totalOutput) * 100, 2))
            : 100;

        // Theoretical capacity (Pairs/min benchmark roughly 1 pair per 4 min per machine)
        // Performance = Actual Output / Theoretical Max
        $theoreticalCapPcs = max(1, (int) ($operatingMinutes * 0.25));
        $performanceRate = $operatingMinutes > 0
            ? min(100, round(($totalProductionPcs / $theoreticalCapPcs) * 100, 2))
            : 85.0;

        // Overall OEE %
        $oeeRate = round(($availabilityRate / 100) * ($performanceRate / 100) * ($qualityRate / 100) * 100, 2);

        // Top 5 Downtime Reasons
        $topReasons = DowntimeReason::withCount(['downtimes' => function ($q) use ($date, $areaId) {
            $q->whereDate('start_time', $date);
            if ($areaId) {
                $q->whereHas('machine', fn ($m) => $m->where('factory_area_id', $areaId));
            }
        }])
        ->orderByDesc('downtimes_count')
        ->take(5)
        ->get();

        // Area summary list
        $areaSummaries = $areas->map(function ($area) use ($date) {
            $mcCount = Machine::where('factory_area_id', $area->id)->count();
            $downtimeMin = (int) MachineDowntime::whereDate('start_time', $date)
                ->whereHas('machine', fn ($q) => $q->where('factory_area_id', $area->id))
                ->sum('duration_minutes');
            $prodPcs = (int) ProductionBox::whereDate('production_date', $date)
                ->where('factory_area_id', $area->id)
                ->sum('good_pcs');

            $loadMin = max(1, $mcCount * 1440);
            $opMin = max(0, $loadMin - $downtimeMin);
            $avail = round(($opMin / $loadMin) * 100, 1);

            return [
                'area_name' => $area->area_name,
                'machines' => $mcCount,
                'downtime_minutes' => $downtimeMin,
                'production_pcs' => $prodPcs,
                'availability' => $avail,
            ];
        });

        return view('machines.oee', compact(
            'date',
            'areaId',
            'areas',
            'machineCount',
            'availabilityRate',
            'performanceRate',
            'qualityRate',
            'oeeRate',
            'totalDowntimeMinutes',
            'totalProductionPcs',
            'totalDefectPcs',
            'topReasons',
            'areaSummaries'
        ));
    }
}
