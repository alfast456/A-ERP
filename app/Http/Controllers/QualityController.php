<?php

namespace App\Http\Controllers;

use App\Enums\ReworkStatus;
use App\Models\BoxDefect;
use App\Models\DefectType;
use App\Models\ReworkTracking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualityController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'defects');

        $defectTypes = DefectType::orderBy('defect_code')->get();

        if ($tab === 'rework') {
            $query = ReworkTracking::with('defectType');

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('defect_type_id')) {
                $query->where('defect_type_id', $request->input('defect_type_id'));
            }

            $reworkList = $query->orderByDesc('id')->paginate(20)->withQueryString();
            $defectList = null;
        } else {
            $query = BoxDefect::with('defectType');

            if ($request->filled('defect_type_id')) {
                $query->where('defect_type_id', $request->input('defect_type_id'));
            }

            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where('notes', 'like', "%{$search}%");
            }

            $defectList = $query->orderByDesc('id')->paginate(20)->withQueryString();
            $reworkList = null;
        }

        $totalDefectsCount = BoxDefect::count();
        $totalReworkCount = ReworkTracking::count();
        $totalRepaired = (int) ReworkTracking::where('status', ReworkStatus::RESOLVED)->sum('repaired_pcs');
        $totalScrapped = (int) ReworkTracking::where('status', ReworkStatus::SCRAPPED)->sum('scrapped_pcs');
        $inRepairCount = ReworkTracking::where('status', ReworkStatus::IN_REPAIR)->count();

        $repairResolutionRate = ($totalRepaired + $totalScrapped) > 0
            ? round(($totalRepaired / ($totalRepaired + $totalScrapped)) * 100, 1)
            : 0;

        return view('quality.index', compact(
            'tab',
            'defectList',
            'reworkList',
            'defectTypes',
            'totalDefectsCount',
            'totalReworkCount',
            'totalRepaired',
            'totalScrapped',
            'inRepairCount',
            'repairResolutionRate'
        ));
    }
}
