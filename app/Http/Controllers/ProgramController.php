<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\ProgramTitleOption;
use App\Services\KpiCalculationService;
use App\Services\ProgramParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramParticipationService $participationService,
        private readonly KpiCalculationService $kpiCalculationService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Program::query();

        if ($this->isGuruOnly($user)) {
            $guruId = $user->guru?->id ?? 0;
            $query->whereHas('gurus', fn ($q) => $q->where('gurus.id', $guruId));
        }

        return view('programs.index', [
            'programs' => $query->latest('program_date')->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);

        $activeTab = $this->programFormTab($request, $user->hasRole('master_admin'));

        return view('programs.form', [
            'program' => new Program(),
            'gurus' => Guru::query()->with('user', 'pasti')->where('active', true)->orderBy('id')->get(),
            'titleOptions' => $this->activeTitleOptions(),
            'selectedGuruIds' => [],
            'defaultTeacherScope' => 'all',
            'activeTab' => $activeTab,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);

        $data = $request->validate([
            'title_option' => ['required', 'string'],
            'title_other' => ['required_if:title_option,other', 'nullable', 'string', 'max:255'],
            'program_date' => ['required', 'date'],
            'program_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'require_absence_reason' => ['nullable', 'boolean'],
            'markah' => ['required', 'integer', 'min:1', 'max:5'],
            'teacher_scope' => ['required', 'in:all,selected'],
            'guru_ids' => ['required_if:teacher_scope,selected', 'array', 'min:1'],
            'guru_ids.*' => ['integer', 'exists:gurus,id'],
        ]);

        $title = $this->resolveProgramTitle($data['title_option'], $data['title_other'] ?? null);

        $program = Program::query()->create([
            'title' => $title,
            'program_date' => $data['program_date'],
            'program_time' => $data['program_time'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'banner_path' => $request->hasFile('banner_image')
                ? $request->file('banner_image')->store('program-banners', 'public')
                : null,
            'require_absence_reason' => (bool) ($data['require_absence_reason'] ?? false),
            'markah' => $data['markah'],
            'created_by' => $user->id,
        ]);

        $allActiveGuruIds = $this->activeGuruIds();
        $guruIds = $data['teacher_scope'] === 'selected'
            ? ($data['guru_ids'] ?? [])
            : $allActiveGuruIds;

        $guruIds = array_values(array_intersect($guruIds, $allActiveGuruIds));
        $this->participationService->syncTeachers($program->id, $guruIds, $user->id);

        Guru::query()->whereIn('id', $guruIds)->get()->each(fn (Guru $guru) => $this->kpiCalculationService->recalculateForGuru($guru));

        return redirect()->route('programs.index')->with('status', __('messages.saved'));
    }

    public function show(Request $request, Program $program): View
    {
        $user = $request->user();

        if ($this->isGuruOnly($user)) {
            $guruId = $user->guru?->id;
            abort_unless($guruId && $program->gurus()->where('gurus.id', $guruId)->exists(), 403);
        }

        return view('programs.show', [
            'program' => $program->load([
                'participations.guru.user',
                'participations.status',
            ]),
            'statuses' => ProgramStatus::query()
                ->whereIn('code', ['HADIR', 'TIDAK_HADIR'])
                ->orderBy('is_hadir', 'desc')
                ->get(),
            'canManage' => $user->hasRole('master_admin') || $user->hasRole('admin'),
            'canUpdateOwn' => $user->hasRole('guru') && (bool) $user->guru,
            'currentGuruId' => $user->guru?->id,
            'isAllTeachers' => $program->gurus()->count() === count($this->activeGuruIds()),
        ]);
    }

    public function edit(Request $request, Program $program): View
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);
        $activeTab = $this->programFormTab($request, $user->hasRole('master_admin'));

        $allActiveGuruIds = $this->activeGuruIds();
        $selectedGuruIds = $program->gurus()->pluck('gurus.id')->all();
        $defaultTeacherScope = count($selectedGuruIds) === count($allActiveGuruIds) ? 'all' : 'selected';

        return view('programs.form', [
            'program' => $program,
            'gurus' => Guru::query()->with('user', 'pasti')->where('active', true)->orderBy('id')->get(),
            'titleOptions' => $this->activeTitleOptions(),
            'selectedGuruIds' => $selectedGuruIds,
            'defaultTeacherScope' => $defaultTeacherScope,
            'activeTab' => $activeTab,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);

        $data = $request->validate([
            'title_option' => ['required', 'string'],
            'title_other' => ['required_if:title_option,other', 'nullable', 'string', 'max:255'],
            'program_date' => ['required', 'date'],
            'program_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'require_absence_reason' => ['nullable', 'boolean'],
            'markah' => ['required', 'integer', 'min:1', 'max:5'],
            'teacher_scope' => ['required', 'in:all,selected'],
            'guru_ids' => ['required_if:teacher_scope,selected', 'array', 'min:1'],
            'guru_ids.*' => ['integer', 'exists:gurus,id'],
        ]);

        $title = $this->resolveProgramTitle($data['title_option'], $data['title_other'] ?? null);

        $program->update([
            'title' => $title,
            'program_date' => $data['program_date'],
            'program_time' => $data['program_time'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'markah' => $data['markah'],
            'require_absence_reason' => (bool) ($data['require_absence_reason'] ?? false),
        ]);

        if ($request->hasFile('banner_image')) {
            $program->update([
                'banner_path' => $request->file('banner_image')->store('program-banners', 'public'),
            ]);
        }

        $existingGuruIds = $program->gurus()->pluck('gurus.id')->all();
        $allActiveGuruIds = $this->activeGuruIds();
        $guruIds = $data['teacher_scope'] === 'selected'
            ? ($data['guru_ids'] ?? [])
            : $allActiveGuruIds;

        $guruIds = array_values(array_intersect($guruIds, $allActiveGuruIds));
        $this->participationService->syncTeachers($program->id, $guruIds, $user->id);

        $affectedGuruIds = array_values(array_unique(array_merge($existingGuruIds, $guruIds)));
        Guru::query()->whereIn('id', $affectedGuruIds)->get()->each(fn (Guru $guru) => $this->kpiCalculationService->recalculateForGuru($guru));

        return redirect()->route('programs.show', $program)->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);

        $guruIds = $program->gurus()->pluck('gurus.id')->all();
        $program->delete();

        Guru::query()->whereIn('id', $guruIds)->get()->each(fn (Guru $guru) => $this->kpiCalculationService->recalculateForGuru($guru));

        return redirect()->route('programs.index')->with('status', __('messages.deleted'));
    }

    private function activeGuruIds(): array
    {
        return Guru::query()->where('active', true)->pluck('id')->all();
    }

    private function activeTitleOptions()
    {
        return ProgramTitleOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'markah']);
    }

    private function resolveProgramTitle(string $selectedValue, ?string $otherTitle): string
    {
        if ($selectedValue === 'other') {
            return trim((string) $otherTitle);
        }

        $optionId = (int) $selectedValue;
        $option = ProgramTitleOption::query()
            ->where('is_active', true)
            ->find($optionId);

        abort_unless($option, 422);

        return $option->title;
    }

    private function isGuruOnly($user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }

    private function programFormTab(Request $request, bool $canManageTitleOptions): string
    {
        $tab = $request->query('tab', 'program');
        $allowedTabs = $canManageTitleOptions
            ? ['program', 'title-options']
            : ['program'];

        if (! in_array($tab, $allowedTabs, true)) {
            return 'program';
        }

        return $tab;
    }
}
