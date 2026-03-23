<?php

namespace App\Http\Controllers;

use App\Models\Pasti;
use App\Models\PastiScore;
use App\Models\PemarkahanTitleOption;
use App\Models\User;
use App\Notifications\PemarkahanSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PemarkahanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        return view('pemarkahan.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $data = $request->validate([
            'title_option_id' => ['required', 'integer', Rule::exists('pemarkahan_title_options', 'id')],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $allowedPastiIds = Pasti::query()
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($q) => $q->whereIn('id', $this->assignedPastiIds($user))
            )
            ->pluck('id')
            ->all();

        $now = now();
        $rowsToUpsert = [];
        $pastiIdsToDelete = [];

        foreach ($allowedPastiIds as $pastiId) {
            $rawScore = $data['scores'][$pastiId] ?? null;

            if ($rawScore === null || $rawScore === '') {
                $pastiIdsToDelete[] = $pastiId;
                continue;
            }

            $rowsToUpsert[] = [
                'pasti_id' => $pastiId,
                'pemarkahan_title_option_id' => (int) $data['title_option_id'],
                'year' => (int) $data['year'],
                'score' => (float) $rawScore,
                'updated_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($data, $rowsToUpsert, $pastiIdsToDelete): void {
            if (! empty($rowsToUpsert)) {
                PastiScore::query()->upsert(
                    $rowsToUpsert,
                    ['pasti_id', 'pemarkahan_title_option_id', 'year'],
                    ['score', 'updated_by', 'updated_at']
                );
            }

            if (! empty($pastiIdsToDelete)) {
                PastiScore::query()
                    ->where('pemarkahan_title_option_id', (int) $data['title_option_id'])
                    ->where('year', (int) $data['year'])
                    ->whereIn('pasti_id', $pastiIdsToDelete)
                    ->delete();
            }
        });

        if (! empty($rowsToUpsert)) {
            $titleOptionName = PemarkahanTitleOption::query()
                ->whereKey((int) $data['title_option_id'])
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

            foreach ($scoreRowsByPasti as $pastiId => $row) {
                $recipients = $guruRecipientsByPasti->get((int) $pastiId, collect());
                if ($recipients->isEmpty()) {
                    continue;
                }

                Notification::send(
                    $recipients,
                    new PemarkahanSubmittedNotification(
                        $titleOptionName,
                        (int) $data['year'],
                        (float) $row['score'],
                        $pastiNames[(int) $pastiId] ?? '-'
                    )
                );
            }
        }

        return redirect()
            ->route('pemarkahan.index', [
                'title_option_id' => (int) $data['title_option_id'],
                'year' => (int) $data['year'],
            ])
            ->with('status', __('messages.saved'));
    }

    public function storeTitleOption(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('pemarkahan_title_options', 'title')],
        ]);

        $maxSortOrder = (int) PemarkahanTitleOption::query()->max('sort_order');

        PemarkahanTitleOption::query()->create([
            'title' => $data['title'],
            'sort_order' => $maxSortOrder + 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return back()->with('status', __('messages.saved'));
    }

    protected function assignedPastiIds(User $user): array
    {
        return $user->assignedPastis()->pluck('pastis.id')->all();
    }
}
