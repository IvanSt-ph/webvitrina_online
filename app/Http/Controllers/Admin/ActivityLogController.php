<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:80'],
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = AdminActivityLog::query()->with('admin')->latest();

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($sub) use ($search) {
                $sub->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('admin', fn ($admin) => $admin->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $query
            ->when($request->filled('action'), fn ($builder) => $builder->where('action', $request->string('action')->toString()))
            ->when($request->filled('admin_id'), fn ($builder) => $builder->where('admin_id', (int) $request->input('admin_id')))
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->date('date_to')));

        $logs = $query->paginate(25)->withQueryString();
        $admins = User::where('role', 'admin')->orderBy('name')->get(['id', 'name']);
        $actions = AdminActivityLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity.index', compact('logs', 'admins', 'actions'));
    }
}
