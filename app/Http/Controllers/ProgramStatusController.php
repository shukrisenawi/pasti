<?php

namespace App\Http\Controllers;

use App\Models\ProgramStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramStatusController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('program-statuses.index', [
            'statuses' => ProgramStatus::query()->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('program-statuses.form', [
            'status' => new ProgramStatus(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:program_statuses,code'],
            'is_hadir' => ['nullable', 'boolean'],
        ]);

        ProgramStatus::query()->create([
            ...$data,
            'is_hadir' => (bool) ($data['is_hadir'] ?? false),
        ]);

        return redirect()->route('program-statuses.index')->with('status', __('messages.saved'));
    }

    public function edit(Request $request, ProgramStatus $program_status): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('program-statuses.form', [
            'status' => $program_status,
        ]);
    }

    public function update(Request $request, ProgramStatus $program_status): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('program_statuses', 'code')->ignore($program_status->id)],
            'is_hadir' => ['nullable', 'boolean'],
        ]);

        $program_status->update([
            ...$data,
            'is_hadir' => (bool) ($data['is_hadir'] ?? false),
        ]);

        return redirect()->route('program-statuses.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, ProgramStatus $program_status): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $program_status->delete();

        return redirect()->route('program-statuses.index')->with('status', __('messages.deleted'));
    }
}
