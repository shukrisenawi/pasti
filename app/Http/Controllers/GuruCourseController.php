<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruCourseOffer;
use App\Models\GuruCourseOfferResponse;
use App\Models\User;
use App\Notifications\GuruCourseOfferNotification;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GuruCourseController extends Controller
{
    private const MIN_SEMESTER = 1;
    private const MAX_SEMESTER = 7;

    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $latestOffers = $this->latestOffersBySemester();
        $pendingReminderGurus = $this->pendingReminderGurusForAdmin($latestOffers);
        $pendingResponses = collect();
        $historyResponses = collect();

        if ($user->isOperatingAsGuru()) {
            $guruId = (int) ($user->guru?->id ?? 0);
            $pendingResponses = GuruCourseOfferResponse::query()
                ->with('offer')
                ->where('guru_id', $guruId)
                ->whereNull('responded_at')
                ->orderByDesc('id')
                ->get();

            $historyResponses = GuruCourseOfferResponse::query()
                ->with('offer')
                ->where('guru_id', $guruId)
                ->whereNotNull('responded_at')
                ->latest('responded_at')
                ->limit(10)
                ->get();
        }

        return view('guru-course.index', [
            'semesterList' => range(self::MIN_SEMESTER, self::MAX_SEMESTER),
            'latestOffers' => $latestOffers,
            'canRequestReminder' => $user->isOperatingAsAdmin() && $pendingReminderGurus->isNotEmpty(),
            'pendingReminderCount' => $pendingReminderGurus->count(),
            'pendingResponses' => $pendingResponses,
            'historyResponses' => $historyResponses,
            'canSendOffer' => $user->isOperatingAsAdmin(),
        ]);
    }

    public function sendOffer(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $data = $request->validate([
            'deadlines' => ['required', 'array'],
            'deadlines.*' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $deadlines = collect($data['deadlines'] ?? [])
            ->mapWithKeys(fn ($deadline, $semester) => [(int) $semester => $deadline])
            ->filter(fn ($deadline, $semester) => (int) $semester >= self::MIN_SEMESTER && (int) $semester <= self::MAX_SEMESTER && filled($deadline));

        if ($deadlines->isEmpty()) {
            return back()->withErrors([
                'kursus_guru' => 'Sila isi sekurang-kurangnya satu tarikh untuk dihantar.',
            ])->withInput();
        }

        $latestOffers = $this->latestOffersBySemester();
        $createdCount = 0;
        $issues = [];

        foreach ($deadlines as $targetSemester => $deadline) {
            $latest = $latestOffers->get((int) $targetSemester);
            $isLocked = $latest
                && (int) $latest->responses_count > 0
                && (int) $latest->responded_count < (int) $latest->responses_count;

            if ($isLocked) {
                $issues[] = 'Semester ' . $targetSemester . ' masih menunggu jawapan guru.';
                continue;
            }

            $recipientGurus = $this->eligibleGurusForOffer($user, (int) $targetSemester)->get();

            if ($recipientGurus->isEmpty()) {
                $issues[] = (int) $targetSemester === self::MIN_SEMESTER
                    ? 'Tiada guru aktif ditemui untuk kategori belum kursus.'
                    : 'Tiada guru aktif ditemui untuk Semester ' . ((int) $targetSemester - 1) . '.';
                continue;
            }

            $createdOffer = null;
            DB::transaction(function () use ($user, $targetSemester, $deadline, $data, $recipientGurus, &$createdOffer): void {
                $offer = GuruCourseOffer::query()->create([
                    'target_semester' => (int) $targetSemester,
                    'registration_deadline' => $deadline,
                    'note' => trim((string) ($data['notes'][$targetSemester] ?? '')) ?: null,
                    'sent_by' => $user->id,
                    'sent_at' => now(),
                ]);

                $responseRows = $recipientGurus->map(fn (Guru $guru): array => [
                    'guru_course_offer_id' => $offer->id,
                    'guru_id' => $guru->id,
                    'user_id' => $guru->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                GuruCourseOfferResponse::query()->insert($responseRows);

                $recipientUsers = $recipientGurus
                    ->map(fn (Guru $guru) => $guru->user)
                    ->filter()
                    ->unique('id')
                    ->values();

                if ($recipientUsers->isNotEmpty()) {
                    Notification::send($recipientUsers, new GuruCourseOfferNotification($offer));
                }

                $createdOffer = $offer;
            });

            if ($createdOffer) {
                $this->n8nWebhookService->sendByTemplate(
                    N8nWebhookService::KEY_TEXT_GURU_COURSE_OFFER,
                    [
                        'semester' => (int) $createdOffer->target_semester,
                        'tarikh_akhir' => $createdOffer->registration_deadline?->format('d/m/Y') ?? '-',
                        'nota' => filled($createdOffer->note) ? (' Nota: ' . trim((string) $createdOffer->note)) : '',
                    ],
                    $this->n8nWebhookService->toActionUrl(route('kursus-guru.index'))
                );
            }

            $createdCount++;
        }

        if ($createdCount === 0) {
            return back()->withErrors([
                'kursus_guru' => implode(' ', $issues) ?: 'Tiada semester berjaya dihantar.',
            ])->withInput();
        }

        if ($issues !== []) {
            return back()
                ->with('status', __('messages.saved'))
                ->with('kursus_guru_warning', implode(' ', $issues));
        }

        return back()->with('status', __('messages.saved'));
    }

    public function requestPendingResponses(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $latestOffers = $this->latestOffersBySemester();
        $pendingReminderGurus = $this->pendingReminderGurusForAdmin($latestOffers);

        if ($pendingReminderGurus->isEmpty()) {
            return back()->with('status', 'Tiada guru layak untuk dihantar.');
        }

        $senaraiGuru = $pendingReminderGurus
            ->values()
            ->map(fn (Guru $guru, int $index) => ($index + 1) . '- ' . $guru->display_name)
            ->implode("\n");

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_GURU_COURSE_RESPONSE_REMINDER,
            ['senarai_guru' => $senaraiGuru],
            $this->n8nWebhookService->toActionUrl(route('kursus-guru.index'))
        );

        return back()->with('status', 'Mesej telah berjaya dihantar ke group guru.');
    }

    public function respond(Request $request, GuruCourseOfferResponse $response): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);

        abort_unless((int) ($user->guru?->id ?? 0) === (int) $response->guru_id, 403);
        abort_if($response->responded_at !== null, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:continue,stop'],
            'stop_reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,stop'],
        ], [
            'stop_reason.required_if' => 'Sila nyatakan alasan jika tidak mahu sambung.',
        ]);

        DB::transaction(function () use ($response, $data): void {
            $response->update([
                'decision' => $data['decision'],
                'stop_reason' => $data['decision'] === 'stop' ? trim((string) ($data['stop_reason'] ?? '')) : null,
                'responded_at' => now(),
            ]);

            if ($data['decision'] === 'continue') {
                $targetSemester = (int) $response->offer->target_semester;
                $response->guru()->update([
                    'kursus_guru' => 'semester_' . $targetSemester,
                ]);
            }
        });

        $realCourseResponses = GuruCourseOfferResponse::query()
            ->with('guru.user')
            ->get()
            ->filter(fn (GuruCourseOfferResponse $response): bool => $response->guru && ! $this->isTestReminderAccount($response->guru));

        $realCourseResponsesCompleted = $realCourseResponses->isNotEmpty()
            && $realCourseResponses->contains(fn (GuruCourseOfferResponse $response): bool => $response->responded_at === null) === false;

        if ($realCourseResponsesCompleted) {
            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                [
                    'perkara' => 'respon sambung kursus guru',
                ],
                $this->n8nWebhookService->toActionUrl(route('kursus-guru.index'))
            );
        }

        return redirect()->route('kursus-guru.index')->with('status', __('messages.saved'));
    }

    private function eligibleGurusForOffer(User $user, int $targetSemester): Builder
    {
        return Guru::query()
            ->with('user')
            ->where('active', true)
            ->whereNotNull('user_id')
            ->when(
                $targetSemester === self::MIN_SEMESTER,
                fn (Builder $query) => $query->where(fn (Builder $subQuery) => $subQuery
                    ->whereNull('kursus_guru')
                    ->orWhere('kursus_guru', 'belum_kursus')
                ),
                fn (Builder $query) => $query->where('kursus_guru', 'semester_' . ($targetSemester - 1))
            )
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('pasti_id', $this->assignedPastiIds($user))
            );
    }

    private function latestOffersBySemester()
    {
        $latestOfferIds = GuruCourseOffer::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('target_semester')
            ->pluck('id');

        return GuruCourseOffer::query()
            ->withCount('responses')
            ->withCount([
                'responses as responded_count' => fn (Builder $query) => $query->whereNotNull('responded_at'),
                'responses as continue_count' => fn (Builder $query) => $query->where('decision', 'continue'),
                'responses as stop_count' => fn (Builder $query) => $query->where('decision', 'stop'),
            ])
            ->whereIn('id', $latestOfferIds)
            ->get()
            ->keyBy(fn (GuruCourseOffer $offer) => (int) $offer->target_semester);
    }

    private function pendingReminderGurusForAdmin(Collection $latestOffers): Collection
    {
        $latestOfferIds = $latestOffers->pluck('id')->all();

        if ($latestOfferIds === []) {
            return collect();
        }

        return GuruCourseOfferResponse::query()
            ->with('guru.user')
            ->whereIn('guru_course_offer_id', $latestOfferIds)
            ->whereNull('responded_at')
            ->get()
            ->pluck('guru')
            ->filter()
            ->reject(fn (Guru $guru): bool => $this->isTestReminderAccount($guru))
            ->unique('id')
            ->sortBy(fn (Guru $guru) => $guru->display_name)
            ->values();
    }

    private function isTestReminderAccount(Guru $guru): bool
    {
        $displayName = trim(mb_strtolower((string) $guru->display_name));
        $guruName = trim(mb_strtolower((string) $guru->name));

        return in_array('test', [$displayName, $guruName], true);
    }
}
