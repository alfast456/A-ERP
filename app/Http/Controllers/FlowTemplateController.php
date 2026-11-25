<?php

namespace App\Http\Controllers;

use App\Enums\DepartmentType;
use App\Models\FlowStep;
use App\Models\FlowTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlowTemplateController extends Controller
{
    public function index()
    {
        $templates = FlowTemplate::with(['steps' => function ($q) {
            $q->orderBy('step_order');
        }])->get();

        $departmentTypes = DepartmentType::cases();

        return view('wip.flow_templates', compact('templates', 'departmentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'steps' => 'required|array|min:1',
            'steps.*' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $template = FlowTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'created_at' => now(),
            ]);

            foreach ($validated['steps'] as $index => $dept) {
                FlowStep::create([
                    'flow_template_id' => $template->id,
                    'step_order' => $index + 1,
                    'department_type' => $dept,
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('wip.flows.index')
            ->with('success', "Master template alur proses '{$validated['name']}' berhasil dibuat.");
    }
}
