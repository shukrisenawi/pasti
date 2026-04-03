<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruCourseOffer;
use App\Models\GuruCourseOfferResponse;
use App\Models\User;
use App\Notifications\GuruCourseOfferNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class GuruCourseController extends Controller
{
    private const MIN_SEMESTER = 1;
    private const MAX_SEMESTER = 7;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $latestOffers = $this->latestOffersBySemester();
        $pendingResponses = collect();
        $historyResponses = collect();

        if ($user->hasRole('guru')) {
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
            'pendingResponses' => $pendingResponses,
            'historyResponses' => $historyResponses,
            'canSendOffer' => $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    public function sendOffer(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $data = $request->validate([
            'target_semester' => ['required', 'integer', 'min:2', 'max:' . self::MAX_SEMESTER],
            'registration_deadline' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $targetSemester = (int) $data['target_semester'];
        $sourceSemester = $targetSemester - 1;

        $recipientGurus = $this->eligibleGurusForOffer($user, $sourceSemester)->get();

        if ($recipientGurus->isEmpty()) {
            return back()->withErrors([
                'kursus_guru' => 'Tiada guru aktif ditemui untuk Semester ' . $sourceSemester . '.',
            ]);
        }

        DB::transaction(function () use ($data, $user, $recipientGurus): void {
            $offer = GuruCourseOffer::query()->create([
                'target_semester' => $data['target_semester'],
                'registration_deadline' => $data['registration_deadline'],
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
        });

        return back()->with('status', __('messages.saved'));
    }

    public function respond(Request $request, GuruCourseOfferResponse $response): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        abort_unless((int) ($user->guru?->id ?? 0) === (int) $response->guru_id, 403);
        abort_if($response->responded_at !== null, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:continue,stop'],
            'stop_reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,stop'],
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

        return redirect()->route('kursus-guru.index')->with('status', __('messages.saved'));
    }

    private function eligibleGurusForOffer(User $user, int $sourceSemester): Builder
    {
        return Guru::query()
            ->with('user')
            ->where('active', true)
            ->whereNotNull('user_id')
            ->where('kursus_guru', 'semester_' . $sourceSemester)
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
}

