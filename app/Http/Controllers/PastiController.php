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
    public function __construct(private readonly GuruProfileCompletionService $profileCompletionService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $query = Pasti::query()->with('kawasan');

        if ($user->hasRole('admin')) {
            $query->whereIn('id', $this->assignedPastiIds($user));
        }

        return view('pasti.index', [
            'pastis' => $query->latest()->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        return view('pasti.form', [
            'pasti' => new Pasti(),
            'kawasans' => Kawasan::query()->orderBy('name')->get(),
            'adminIds' => [],
            'isOwnUpdate' => false,
            'isOnboardingStep' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $data = $request->validate($this->validationRules());

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
        abort_if($user->hasRole('guru'), 403);

        $this->ensurePastiAllowed($user, $pasti);

        return view('pasti.form', [
            'pasti' => $pasti,
            'kawasans' => Kawasan::query()->orderBy('name')->get(),
            'adminIds' => $pasti->admins()->pluck('users.id')->all(),
            'isOwnUpdate' => false,
            'isOnboardingStep' => false,
        ]);
    }

    public function update(Request $request, Pasti $pasti): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('guru'), 403);

        $this->ensurePastiAllowed($user, $pasti);

        $data = $request->validate($this->validationRules($pasti->id));

        $pasti->update($data);
        $this->syncPastiImage($request, $pasti);

        return redirect()->route('pasti.index')->with('status', __('messages.saved'));
    }

    public function editOwn(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        $pasti = $user->guru?->pasti;
        abort_unless($pasti, 403);

        return view('pasti.form', [
            'pasti' => $pasti,
            'kawasans' => Kawasan::query()->orderBy('name')->get(),
            'adminIds' => [],
            'isOwnUpdate' => true,
            'isOnboardingStep' => $request->query('step') === 'onboarding',
        ]);
    }

    public function updateOwn(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        $pasti = $user->guru?->pasti;
        abort_unless($pasti, 403);

        $data = $request->validate($this->ownValidationRules($pasti->id));
        $pasti->update($data);
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

        abort_unless(in_array($pasti->id, $this->assignedPastiIds($user), true), 403);
    }

    private function validationRules(?int $pastiId = null): array
    {
        return [
            'kawasan_id' => ['required', 'integer', 'exists:kawasans,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', $pastiId ? Rule::unique('pastis', 'code')->ignore($pastiId) : 'unique:pastis,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_phone' => ['nullable', 'string', 'max:30'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    private function ownValidationRules(?int $pastiId = null): array
    {
        return [
            'kawasan_id' => ['required', 'integer', 'exists:kawasans,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', $pastiId ? Rule::unique('pastis', 'code')->ignore($pastiId) : 'unique:pastis,code'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_phone' => ['required', 'string', 'max:30'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
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
