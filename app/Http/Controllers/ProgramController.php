<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\ProgramTitleOption;
use App\Models\User;
use App\Notifications\ProgramAssignedNotification;
use App\Services\KpiCalculationService;
use App\Services\N8nWebhookService;
use App\Services\ProgramParticipationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(
        private readonly ProgramParticipationService $participationService,
        private readonly KpiCalculationService $kpiCalculationService,
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        return view('programs.index');
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);

        $activeTab = $this->programFormTab($request, $user->hasRole('master_admin'));

        return view('programs.form', [
            'program' => new Program(),
            'gurus' => $this->eligibleProgramGurusQuery()->get(),
            'titleOptions' => $this->activeTitleOptions(),
            'allTitleOptions' => $this->allTitleOptions(),
            'editingTitleOption' => $this->editingTitleOption($request),
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

        $affectedGurus = Guru::query()->with('user')->whereIn('id', $guruIds)->get();
        $affectedGurus->each(fn (Guru $guru) => $this->kpiCalculationService->recalculateForGuru($guru));
        $this->notifyAssignedUsers($program, $affectedGurus, $user, 'ditambah');
        $this->sendProgramCreatedWebhook($program);

        return redirect()->route('programs.index')->with('status', __('messages.saved'));
    }

    public function show(Request $request, Program $program): View
    {
        $user = $request->user();
        $operatingGurus = $user->operatingGuruProfiles();
        $operatingGurus = $operatingGurus->reject(fn (Guru $guru): bool => $guru->is_assistant)->values();
        $operatingGuruIds = $operatingGurus->pluck('id')->map(fn ($id) => (int) $id)->all();
        $operatingGuru = $operatingGurus->first();

        if ($this->isGuruOnly($user)) {
            abort_unless($operatingGuruIds !== [], 403);
            $canAccessProgram = $program->gurus()->whereIn('gurus.id', $operatingGuruIds)->exists()
                || $program->pasti_id === null;
            abort_unless($canAccessProgram, 403);
        }

        $program->load([
            'participations.guru.user',
            'participations.status',
        ]);
        $allParticipations = $program->participations->sortByDesc(
            fn ($participation) => optional($participation->updated_at)->getTimestamp() ?? 0
        )->values();
        $displayParticipations = $this->displayParticipations($allParticipations);
        $submittedParticipations = $displayParticipations
            ->filter(fn ($participation) => filled($participation->program_status_id))
            ->values();
        $pendingResponseParticipations = $displayParticipations
            ->filter(fn ($participation) => blank($participation->program_status_id))
            ->values();
        $pendingReminderGurus = $this->pendingReminderGurusForProgram($program);
        $currentGuruId = $operatingGuru?->id;
        $currentParticipation = $operatingGuruIds !== []
            ? $allParticipations->first(fn ($participation) => in_array((int) $participation->guru_id, $operatingGuruIds, true))
            : null;
        $adminPendingReviewParticipations = $displayParticipations
            ->filter(fn ($participation) => $participation->absence_reason_status === ProgramParticipationService::ABSENCE_REASON_PENDING)
            ->values();
        $adminCompletedParticipations = $displayParticipations
            ->filter(function ($participation) {
                if (blank($participation->program_status_id)) {
                    return false;
                }

                return $participation->absence_reason_status !== ProgramParticipationService::ABSENCE_REASON_PENDING;
            })
            ->values();

        $program->setRelation('participations', $allParticipations);

        return view('programs.show', [
            'program' => $program,
            'allParticipations' => $displayParticipations,
            'submittedParticipations' => $submittedParticipations,
            'pendingResponseParticipations' => $pendingResponseParticipations,
            'statuses' => ProgramStatus::query()
                ->whereIn('code', ['HADIR', 'TIDAK_HADIR'])
                ->orderBy('is_hadir', 'desc')
                ->get(),
            'canManage' => $user->hasRole('master_admin') || $user->hasRole('admin'),
            'canRequestReminder' => ($user->hasRole('master_admin') || $user->hasRole('admin')) && $pendingReminderGurus->isNotEmpty(),
            'programPendingReminderCount' => $pendingReminderGurus->count(),
            'canUpdateOwn' => $user->isOperatingAsGuru() && (bool) $operatingGuru,
            'currentGuruId' => $currentGuruId,
            'currentParticipation' => $currentParticipation,
            'adminPendingReviewParticipations' => $adminPendingReviewParticipations,
            'adminCompletedParticipations' => $adminCompletedParticipations,
            'isAllTeachers' => $program->gurus()->count() === count($this->activeGuruIds()),
        ]);
    }

    public function edit(Request $request, Program $program): View
    {
        $user = $request->user();
        abort_if($this->isGuruOnly($user), 403);
        $activeTab = $this->programFormTab($request, $user->hasRole('master_admin'));

        $allActiveGuruIds = $this->activeGuruIds();
        $selectedGuruIds = $program->gurus()->where('gurus.is_assistant', false)->pluck('gurus.id')->all();
        $defaultTeacherScope = count($selectedGuruIds) === count($allActiveGuruIds) ? 'all' : 'selected';

        return view('programs.form', [
            'program' => $program,
            'gurus' => $this->eligibleProgramGurusQuery()->get(),
            'titleOptions' => $this->activeTitleOptions(),
            'allTitleOptions' => $this->allTitleOptions(),
            'editingTitleOption' => $this->editingTitleOption($request),
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

        $newlyAddedGuruIds = array_values(array_diff($guruIds, $existingGuruIds));
        if ($newlyAddedGuruIds !== []) {
            $newlyAddedGurus = Guru::query()->with('user')->whereIn('id', $newlyAddedGuruIds)->get();
            $this->notifyAssignedUsers($program, $newlyAddedGurus, $user, 'ditambah');
        }

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

    public function requestPendingResponses(Request $request, Program $program): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $program->loadMissing('participations.guru.user');
        $pendingGurus = $this->pendingReminderGurusForProgram($program);

        if ($pendingGurus->isEmpty()) {
            return back()->with('status', 'Tiada guru layak untuk dihantar.');
        }

        $senaraiGuru = $pendingGurus
            ->values()
            ->map(fn (Guru $guru, int $index) => ($index + 1) . '- ' . $guru->display_name)
            ->implode("\n");

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_PROGRAM_RESPONSE_REMINDER,
            [
                'program_title' => trim((string) $program->title),
                'senarai_guru' => $senaraiGuru,
            ],
            $this->n8nWebhookService->toActionUrl(route('programs.show', $program))
        );

        return back()->with('status', 'Mesej telah berjaya dihantar ke group guru.');
    }

    private function activeGuruIds(): array
    {
        return $this->eligibleProgramGurusQuery()->pluck('gurus.id')->all();
    }

    private function activeTitleOptions()
    {
        return ProgramTitleOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'markah']);
    }

    private function allTitleOptions()
    {
        return ProgramTitleOption::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'markah', 'is_active']);
    }

    private function editingTitleOption(Request $request): ?ProgramTitleOption
    {
        $editId = (int) $request->integer('edit_title_option');
        if ($editId <= 0) {
            return null;
        }

        return ProgramTitleOption::query()->find($editId);
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
        return $user->isOperatingAsGuru();
    }

    private function notifyAssignedUsers(Program $program, Collection $gurus, User $actor, string $action): void
    {
        $users = $gurus
            ->map(fn (Guru $guru) => $guru->user)
            ->filter()
            ->unique('id')
            ->values();

        foreach ($users as $recipient) {
            $recipient->notify(new ProgramAssignedNotification($program, $actor, $action));
        }
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

    private function sendProgramCreatedWebhook(Program $program): void
    {
        $date = $program->program_date instanceof Carbon
            ? $program->program_date
            : Carbon::parse((string) $program->program_date);
        $dayName = $this->dayNameInMalay($date);
        $timeText = $this->programTimeText($program);
        $locationText = filled($program->location) ? ' di ' . trim((string) $program->location) : '';
        $gambar = $this->n8nWebhookService->toPublicUrl($program->banner_url);
        $link = $this->n8nWebhookService->toActionUrl(route('programs.show', $program));

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_PROGRAM_CREATED,
            [
                'tajuk' => trim((string) $program->title),
                'tarikh' => $date->format('j/n/Y'),
                'hari' => $dayName,
                'masa' => $timeText,
                'lokasi' => $locationText,
            ],
            $link,
            $gambar
        );
    }

    private function isTestReminderAccount(Guru $guru): bool
    {
        $displayName = trim(mb_strtolower((string) $guru->display_name));
        $guruName = trim(mb_strtolower((string) $guru->name));

        return in_array('test', [$displayName, $guruName], true);
    }

    private function pendingReminderGurusForProgram(Program $program): Collection
    {
        return $program->participations
            ->whereNull('program_status_id')
            ->pluck('guru')
            ->filter()
            ->reject(fn (Guru $guru): bool => $guru->is_assistant)
            ->reject(fn (Guru $guru): bool => $this->isTestReminderAccount($guru))
            ->unique('id')
            ->sortBy(fn (Guru $guru) => $guru->display_name)
            ->values();
    }

    private function displayParticipations(Collection $participations): Collection
    {
        return $participations
            ->reject(function ($participation): bool {
                $guru = $participation->guru;

                return $guru instanceof Guru
                    && ($guru->is_assistant || $this->isTestReminderAccount($guru));
            })
            ->values();
    }

    private function eligibleProgramGurusQuery()
    {
        return Guru::query()
            ->with('user', 'pasti')
            ->where('active', true)
            ->where('is_assistant', false)
            ->orderBy('id');
    }

    private function dayNameInMalay(Carbon $date): string
    {
        $weekdayMap = [
            'Sunday' => 'Ahad',
            'Monday' => 'Isnin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Khamis',
            'Friday' => 'Jumaat',
            'Saturday' => 'Sabtu',
        ];

        return $weekdayMap[$date->englishDayOfWeek] ?? $date->englishDayOfWeek;
    }

    private function programTimeText(Program $program): string
    {
        if ($program->program_time) {
            $time = $program->program_time instanceof Carbon
                ? $program->program_time
                : Carbon::parse((string) $program->program_time);
            $hour = (int) $time->format('G');
            $suffix = $hour < 12 ? 'pg' : ($hour < 19 ? 'ptg' : 'mlm');

            return ' jam ' . $time->format('g:i') . $suffix;
        }

        return '';
    }
}
