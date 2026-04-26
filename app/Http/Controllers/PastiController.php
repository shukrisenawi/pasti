<?php

namespace App\Http\Controllers;

use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Support\GuruProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PastiController extends Controller
{
    private const DUN_OPTIONS = ['JENERI', 'BELANTEK'];

    public function __construct(private readonly GuruProfileCompletionService $profileCompletionService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user->isOperatingAsGuru(), 403);

        $activeTab = strtolower((string) $request->query('tab', 'jeneri'));
        if (! in_array($activeTab, ['jeneri', 'belantek'], true)) {
            $activeTab = 'jeneri';
        }
        $activeDun = strtoupper($activeTab);

        $scopeQuery = Pasti::query();
        if ($user->hasRole('admin')) {
            $scopeQuery->whereIn('pastis.id', $this->assignedPastiIds($user));
        }

        $query = (clone $scopeQuery)
            ->with('kawasan')
            ->leftJoin('kawasans', 'kawasans.id', '=', 'pastis.kawasan_id')
            ->select('pastis.*')
            ->where('kawasans.dun', $activeDun);

        return view('pasti.index', [
            'pastis' => $query
                ->orderBy('kawasans.dun')
                ->orderBy('pastis.name')
                ->paginate(9)
                ->withQueryString(),
            'activeTab' => $activeTab,
            'jeneriCount' => (clone $scopeQuery)
                ->whereHas('kawasan', fn ($q) => $q->where('dun', 'JENERI'))
                ->count(),
            'belantekCount' => (clone $scopeQuery)
                ->whereHas('kawasan', fn ($q) => $q->where('dun', 'BELANTEK'))
                ->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if($user->isOperatingAsGuru(), 403);

        return view('pasti.form', [
            'pasti' => new Pasti(),
            'dunOptions' => self::DUN_OPTIONS,
            'adminIds' => [],
            'isOwnUpdate' => false,
            'isOnboardingStep' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isOperatingAsGuru(), 403);

        $validated = $request->validate($this->validationRules());
        $data = $this->preparePastiPayload($validated);

        $pasti = Pasti::query()->create($data);
        $this->syncPastiImage($request, $pasti);

        User::query()
            ->role('admin')
            ->where('admin_assignment_scope', 'all')
            ->get()
            ->each(fn (User $admin) => $admin->assignedPastis()->syncWithoutDetaching([$pasti->id]));

        if ($user->hasRole('admin')) {
            $user->assignedPastis()->syncWithoutDetaching([$pasti->id]);
        }

        return redirect()->route('pasti.index')->with('status', __('messages.saved'));
    }

    public function edit(Request $request, Pasti $pasti): View
    {
        $user = $request->user();
        abort_if($user->isOperatingAsGuru(), 403);

        $this->ensurePastiAllowed($user, $pasti);

        return view('pasti.form', [
            'pasti' => $pasti,
            'dunOptions' => self::DUN_OPTIONS,
            'adminIds' => $pasti->admins()->pluck('users.id')->all(),
            'isOwnUpdate' => false,
            'isOnboardingStep' => false,
        ]);
    }

    public function update(Request $request, Pasti $pasti): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isOperatingAsGuru(), 403);

        $this->ensurePastiAllowed($user, $pasti);

        $validated = $request->validate($this->validationRules($pasti->id));
        $data = $this->preparePastiPayload($validated);

        $pasti->update($data);
        $this->syncPastiImage($request, $pasti);

        return redirect()->route('pasti.index')->with('status', __('messages.saved'));
    }

    public function editOwn(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);

        $pasti = $user->guru?->pasti;
        abort_unless($pasti, 403);

        return view('pasti.form', [
            'pasti' => $pasti,
            'dunOptions' => self::DUN_OPTIONS,
            'adminIds' => [],
            'isOwnUpdate' => true,
            'isOnboardingStep' => $request->query('step') === 'onboarding',
        ]);
    }

    public function updateOwn(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);

        $pasti = $user->guru?->pasti;
        abort_unless($pasti, 403);

        $validated = $request->validate($this->ownValidationRules($pasti));
        $pasti->update($validated);
        $this->syncPastiImage($request, $pasti);

        $status = $this->profileCompletionService->onboardingStatus($user->fresh()->loadMissing('guru.pasti'));

        if ($status['profile_completed'] && $status['pasti_completed'] && $status['password_change_required']) {
            return redirect()
                ->route('profile.edit', ['step' => 'password'])
                ->with('status', __('messages.saved'))
                ->with('wizard_notice', 'Maklumat PASTI berjaya dikemaskini. Seterusnya, sila tukar kata laluan anda.');
        }

        return redirect()->route('pasti.self.edit')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Pasti $pasti): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $this->isMasterAdmin($user), 403);

        $pasti->delete();

        return redirect()->route('pasti.index')->with('status', __('messages.deleted'));
    }

    private function ensurePastiAllowed($user, Pasti $pasti): void
    {
        if ($this->isMasterAdmin($user)) {
            return;
        }

        abort_unless(in_array($pasti->id, $this->assignedPastiIds($user)), 403);
    }

    private function validationRules(?int $pastiId = null): array
    {
        return [
            'dun' => ['required', 'string', Rule::in(self::DUN_OPTIONS)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', $pastiId ? Rule::unique('pastis', 'code')->ignore($pastiId) : 'unique:pastis,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_phone' => ['nullable', 'string', 'max:30'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
        ];
    }

    private function ownValidationRules(Pasti $pasti): array
    {
        $imageRules = ['image', 'mimes:jpg,jpeg,png,webp', 'max:7168'];
        array_unshift($imageRules, filled($pasti->image_path) ? 'nullable' : 'required');

        return [
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_phone' => ['required', 'string', 'max:30'],
            'image' => $imageRules,
        ];
    }

    private function preparePastiPayload(array $validated): array
    {
        $dun = $validated['dun'];
        $kawasan = Kawasan::query()->firstOrCreate(
            ['dun' => $dun],
            ['name' => $dun]
        );

        unset($validated['dun']);
        $validated['kawasan_id'] = $kawasan->id;

        return $validated;
    }

    private function syncPastiImage(Request $request, Pasti $pasti): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        if ($pasti->image_path) {
            Storage::disk('public')->delete($pasti->image_path);
        }

        $pasti->update([
            'image_path' => $request->file('image')->store('pasti-images', 'public'),
        ]);
    }
}


