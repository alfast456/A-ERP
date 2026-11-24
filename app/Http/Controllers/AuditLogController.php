<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->input('table_name'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('table_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('record_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate(25)->withQueryString();

        $totalLogsCount = AuditLog::count();
        $distinctModules = AuditLog::distinct()->orderBy('table_name')->pluck('table_name');
        $distinctActions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('audit.index', compact(
            'logs',
            'totalLogsCount',
            'distinctModules',
            'distinctActions'
        ));
    }
}
