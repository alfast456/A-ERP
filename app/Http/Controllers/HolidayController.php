<?php

namespace App\Http\Controllers;

use App\Models\CompanyHoliday;
use App\Models\Plant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    /**
     * Master Kalender Libur Pabrik
     */
    public function index(Request $request): View
    {
        $year = $request->query('year', date('Y'));

        $holidays = CompanyHoliday::with('plant')
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date', 'asc')
            ->paginate(30);

        $stats = [
            'total_holidays' => CompanyHoliday::whereYear('holiday_date', $year)->count(),
            'working_holidays' => CompanyHoliday::whereYear('holiday_date', $year)->where('is_working_day', true)->count(),
            'non_working_holidays' => CompanyHoliday::whereYear('holiday_date', $year)->where('is_working_day', false)->count(),
        ];

        $plants = Plant::all();

        return view('planning.holidays', compact('holidays', 'stats', 'plants', 'year'));
    }

    /**
     * Tambah Hari Libur Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date|unique:company_holidays,holiday_date',
            'description' => 'required|string|max:255',
            'is_working_day' => 'boolean',
            'plant_id' => 'nullable|exists:plants,id',
        ]);

        $validated['is_working_day'] = $request->has('is_working_day');
        $validated['plant_id'] = $validated['plant_id'] ?? 1;
        $validated['created_at'] = now();

        CompanyHoliday::create($validated);

        return back()->with('success', "Hari libur tanggal {$validated['holiday_date']} ({$validated['description']}) berhasil ditambahkan.");
    }

    /**
     * Hapus Hari Libur
     */
    public function destroy(CompanyHoliday $holiday): RedirectResponse
    {
        $desc = $holiday->description;
        $date = $holiday->holiday_date->format('Y-m-d');
        $holiday->delete();

        return back()->with('success', "Hari libur {$date} ({$desc}) berhasil dihapus.");
    }
}
