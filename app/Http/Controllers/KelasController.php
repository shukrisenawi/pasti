<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasStudentCount;
use App\Models\Pasti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $query = Kelas::query()->with(['pasti', 'studentCount']);

        if ($user->hasRole('admin')) {
            $query->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        return view('kelas.index', [
            'kelasCollection' => $query->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        return view('kelas.form', [
            'kelas' => new Kelas(),
            'pastis' => $this->pastisForUser($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $data = $request->validate([
            'pasti_id' => ['required', 'integer', 'exists:pastis,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kelas', 'name')->where(fn ($q) => $q->where('pasti_id', $request->integer('pasti_id'))),
            ],
        ]);

        $this->ensurePastiAllowed($user, $data['pasti_id']);

        $kelas = Kelas::query()->create($data);

        KelasStudentCount::query()->create([
            'kelas_id' => $kelas->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('kelas.index')->with('status', __('messages.saved'));
    }

    public function edit(Request $request, Kelas $kela): View
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $this->ensurePastiAllowed($user, $kela->pasti_id);

        return view('kelas.form', [
            'kelas' => $kela,
            'pastis' => $this->pastisForUser($user),
        ]);
    }

    public function update(Request $request, Kelas $kela): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $data = $request->validate([
            'pasti_id' => ['required', 'integer', 'exists:pastis,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->validate([
            'name' => [
                Rule::unique('kelas', 'name')
                    ->where(fn ($q) => $q->where('pasti_id', $data['pasti_id']))
                    ->ignore($kela->id),
            ],
        ]);

        $this->ensurePastiAllowed($user, $data['pasti_id']);
        $kela->update($data);

        return redirect()->route('kelas.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Kelas $kela): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $this->ensurePastiAllowed($user, $kela->pasti_id);
        $kela->delete();

        return redirect()->route('kelas.index')->with('status', __('messages.deleted'));
    }

    public function updateStudentCount(Request $request, Kelas $kela): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $this->ensurePastiAllowed($user, $kela->pasti_id);

        $data = $request->validate([
            'lelaki_count' => ['required', 'integer', 'min:0'],
            'perempuan_count' => ['required', 'integer', 'min:0'],
        ]);

        $kela->studentCount()->updateOrCreate(
            ['kelas_id' => $kela->id],
            [
                'lelaki_count' => $data['lelaki_count'],
                'perempuan_count' => $data['perempuan_count'],
                'updated_by' => $user->id,
            ]
        );

        return redirect()->route('kelas.index')->with('status', __('messages.saved'));
    }

    private function pastisForUser($user)
    {
        if ($this->isMasterAdmin($user)) {
            return Pasti::query()->orderBy('name')->get();
        }

        return $user->assignedPastis()->orderBy('name')->get();
    }

    private function ensurePastiAllowed($user, int $pastiId): void
    {
        if ($this->isMasterAdmin($user)) {
            return;
        }

        abort_unless(in_array($pastiId, $this->assignedPastiIds($user), true), 403);
    }
}
