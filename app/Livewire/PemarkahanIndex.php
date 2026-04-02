<?php

namespace App\Livewire;

use App\Models\Pasti;
use App\Models\PastiScore;
use App\Models\PemarkahanTitleOption;
use App\Models\User;
use App\Notifications\PemarkahanSubmittedNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PemarkahanIndex extends Component
{
    public string $activeTab = 'scores';

    public int $selectedTitleOptionId = 0;

    public int $selectedYear;

    public array $scoresInput = [];

    public string $newTitle = '';

    public ?int $editingTitleOptionId = null;

    public string $editingTitle = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $this->selectedYear = (int) now()->year;
        if ($this->isGuruOnly($user)) {
            $this->activeTab = 'history';

            return;
        }

        $tab = (string) request()->query('tab', 'scores');
        $allowedTabs = $user->hasRole('master_admin')
            ? ['scores', 'pasti-scores', 'title-options']
            : ['scores', 'pasti-scores'];

        $this->activeTab = in_array($tab, $allowedTabs, true) ? $tab : 'scores';
    }

    public function render()
    {
        /** @var User $user */
        $user = auth()->user();

        $titleOptions = PemarkahanTitleOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($this->isGuruOnly($user)) {
            $guruPastiId = $user->guru?->pasti_id;
            abort_unless($guruPastiId, 403);

            $scores = PastiScore::query()
                ->with('titleOption')
                ->where('pasti_id', $guruPastiId)
                ->orderByDesc('year')
                ->orderBy('pemarkahan_title_option_id')
                ->get();

            return view('livewire.pemarkahan-index', [
                'isGuruOnly' => true,
                'scores' => $scores,
                'pastiName' => Pasti::query()->whereKey($guruPastiId)->value('name'),
                'titleOptions' => $titleOptions,
                'allTitleOptions' => collect(),
                'pastis' => collect(),
                'canManageTitleOptions' => false,
            ]);
        }

        $pastis = $this->accessiblePastisForUser($user);
        $this->fillScoresInput($pastis);

        return view('livewire.pemarkahan-index', [
            'isGuruOnly' => false,
            'scores' => collect(),
            'pastiName' => null,
            'titleOptions' => $titleOptions,
            'allTitleOptions' => PemarkahanTitleOption::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'pastis' => $pastis,
            'canManageTitleOptions' => $user->hasRole('master_admin'),
        ]);
    }

    public function switchTab(string $tab): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($this->isGuruOnly($user)) {
            $this->activeTab = 'history';

            return;
        }

        if (! in_array($tab, ['scores', 'pasti-scores', 'title-options'], true)) {
            return;
        }

        if ($tab === 'title-options' && ! $user->hasRole('master_admin')) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function updatedSelectedTitleOptionId(): void
    {
        $this->scoresInput = [];
    }

    public function updatedSelectedYear(): void
    {
        $this->scoresInput = [];
    }

    public function saveScores(): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $validated = $this->validate([
            'selectedTitleOptionId' => ['required', 'integer', Rule::exists('pemarkahan_title_options', 'id')],
            'selectedYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'scoresInput' => ['nullable', 'array'],
            'scoresInput.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $allowedPastis = $this->accessiblePastisForUser($user);
        $allowedPastiIds = $allowedPastis->pluck('id')->all();

        $now = now();
        $rowsToUpsert = [];
        $pastiIdsToDelete = [];

        foreach ($allowedPastiIds as $pastiId) {
            $rawScore = $validated['scoresInput'][(string) $pastiId] ?? $validated['scoresInput'][$pastiId] ?? null;

            if ($rawScore === null || $rawScore === '') {
                $pastiIdsToDelete[] = $pastiId;
                continue;
            }

            $rowsToUpsert[] = [
                'pasti_id' => $pastiId,
                'pemarkahan_title_option_id' => (int) $validated['selectedTitleOptionId'],
                'year' => (int) $validated['selectedYear'],
                'score' => (float) $rawScore,
                'updated_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($validated, $rowsToUpsert, $pastiIdsToDelete): void {
            if ($rowsToUpsert !== []) {
                PastiScore::query()->upsert(
                    $rowsToUpsert,
                    ['pasti_id', 'pemarkahan_title_option_id', 'year'],
                    ['score', 'updated_by', 'updated_at']
                );
            }

            if ($pastiIdsToDelete !== []) {
                PastiScore::query()
                    ->where('pemarkahan_title_option_id', (int) $validated['selectedTitleOptionId'])
                    ->where('year', (int) $validated['selectedYear'])
                    ->whereIn('pasti_id', $pastiIdsToDelete)
                    ->delete();
            }
        });

        $this->sendScoreNotifications(
            $rowsToUpsert,
            (int) $validated['selectedTitleOptionId'],
            (int) $validated['selectedYear']
        );

        $this->scoresInput = [];
        $this->notice = __('messages.saved');
    }

    public function saveTitleOption(): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $validated = $this->validate([
            'newTitle' => ['required', 'string', 'max:255', Rule::unique('pemarkahan_title_options', 'title')],
        ]);

        $maxSortOrder = (int) PemarkahanTitleOption::query()->max('sort_order');

        PemarkahanTitleOption::query()->create([
            'title' => $validated['newTitle'],
            'sort_order' => $maxSortOrder + 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->newTitle = '';
        $this->editingTitleOptionId = null;
        $this->editingTitle = '';
        $this->notice = __('messages.saved');
    }

    public function startEditTitleOption(int $titleOptionId): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $titleOption = PemarkahanTitleOption::query()->findOrFail($titleOptionId);
        $this->editingTitleOptionId = $titleOption->id;
        $this->editingTitle = $titleOption->title;
    }

    public function cancelEditTitleOption(): void
    {
        $this->editingTitleOptionId = null;
        $this->editingTitle = '';
    }

    public function updateTitleOption(): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('master_admin'), 403);

        if (! $this->editingTitleOptionId) {
            return;
        }

        $validated = $this->validate([
            'editingTitle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pemarkahan_title_options', 'title')->ignore($this->editingTitleOptionId),
            ],
        ]);

        PemarkahanTitleOption::query()
            ->whereKey($this->editingTitleOptionId)
            ->update(['title' => $validated['editingTitle']]);

        $this->cancelEditTitleOption();
        $this->notice = __('messages.saved');
    }

    public function deleteTitleOption(int $titleOptionId): void
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $titleOption = PemarkahanTitleOption::query()->findOrFail($titleOptionId);

        if ($titleOption->scores()->exists()) {
            $this->addError('titleOptionAction', 'Tajuk ini tidak boleh dipadam kerana sudah digunakan dalam markah.');

            return;
        }

        $titleOption->delete();

        if ($this->editingTitleOptionId === $titleOptionId) {
            $this->cancelEditTitleOption();
        }

        $this->notice = __('messages.deleted');
    }

    public function hydrateScoresInput($value = null): void
    {
    }

    private function fillScoresInput(EloquentCollection $pastis): void
    {
        if ($this->selectedTitleOptionId <= 0 || $this->scoresInput !== []) {
            return;
        }

        $existingScores = PastiScore::query()
            ->where('pemarkahan_title_option_id', $this->selectedTitleOptionId)
            ->where('year', $this->selectedYear)
            ->whereIn('pasti_id', $pastis->pluck('id')->all())
            ->pluck('score', 'pasti_id');

        $this->scoresInput = [];
        foreach ($pastis as $pasti) {
            $this->scoresInput[(string) $pasti->id] = $existingScores[$pasti->id] ?? '';
        }
    }

    private function accessiblePastisForUser(User $user): EloquentCollection
    {
        return Pasti::query()
            ->with('kawasan')
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->whereIn('id', $this->assignedPastiIds($user))
            )
            ->orderBy('name')
            ->get();
    }

    private function assignedPastiIds(User $user): array
    {
        return $user->assignedPastis()->pluck('pastis.id')->all();
    }

    private function isGuruOnly(User $user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }

    private function sendScoreNotifications(array $rowsToUpsert, int $titleOptionId, int $year): void
    {
        if ($rowsToUpsert === []) {
            return;
        }

        $titleOptionName = PemarkahanTitleOption::query()
            ->whereKey($titleOptionId)
            ->value('title') ?? '-';

        $scoreRowsByPasti = collect($rowsToUpsert)->keyBy('pasti_id');
        $pastiNames = Pasti::query()
            ->whereIn('id', array_keys($scoreRowsByPasti->all()))
            ->pluck('name', 'id');

        $guruRecipientsByPasti = User::query()
            ->select('users.*')
            ->role('guru')
            ->whereHas('guru', fn ($q) => $q
                ->whereIn('pasti_id', array_keys($scoreRowsByPasti->all()))
                ->where('active', true)
            )
            ->with('guru')
            ->get()
            ->groupBy(fn (User $recipient): int => (int) ($recipient->guru?->pasti_id ?? 0));

        /** @var Collection<int, array<string, mixed>> $scoreRowsByPasti */
        foreach ($scoreRowsByPasti as $pastiId => $row) {
            $recipients = $guruRecipientsByPasti->get((int) $pastiId, collect());
            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send(
                $recipients,
                new PemarkahanSubmittedNotification(
                    $titleOptionName,
                    $year,
                    (float) $row['score'],
                    $pastiNames[(int) $pastiId] ?? '-'
                )
            );
        }
    }
}

