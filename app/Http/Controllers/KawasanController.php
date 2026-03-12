<?php

namespace App\Http\Controllers;

use App\Models\Kawasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KawasanController extends Controller
{
    private const DUN_OPTIONS = ['JENERI', 'BELANTEK'];

    public function index(Request $request): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('kawasan.index', [
            'kawasans' => Kawasan::query()->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('kawasan.form', [
            'kawasan' => new Kawasan(),
            'dunOptions' => self::DUN_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dun' => ['required', 'string', Rule::in(self::DUN_OPTIONS)],
        ]);

        Kawasan::query()->create($data);

        return redirect()->route('kawasan.index')->with('status', __('messages.saved'));
    }

    public function edit(Request $request, Kawasan $kawasan): View
    {
        abort_if($request->user()->hasRole('guru'), 403);

        return view('kawasan.form', [
            'kawasan' => $kawasan,
            'dunOptions' => self::DUN_OPTIONS,
        ]);
    }

    public function update(Request $request, Kawasan $kawasan): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dun' => ['required', 'string', Rule::in(self::DUN_OPTIONS)],
        ]);

        $kawasan->update($data);

        return redirect()->route('kawasan.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Kawasan $kawasan): RedirectResponse
    {
        abort_if($request->user()->hasRole('guru'), 403);

        $kawasan->delete();

        return redirect()->route('kawasan.index')->with('status', __('messages.deleted'));
    }
}
