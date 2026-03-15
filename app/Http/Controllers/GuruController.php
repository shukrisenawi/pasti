<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Pasti;
use App\Models\User;
use App\Services\KpiCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuruController extends Controller
{
    public function __construct(private readonly KpiCalculationService $kpiCalculationService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $activeTab = $request->query('tab', 'guru');
        if (! in_array($activeTab, ['guru', 'assistant'], true)) {
            $activeTab = 'guru';
        }

        $scopeQuery = Guru::query();

        if ($user->hasRole('admin')) {
            $scopeQuery->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        $query = (clone $scopeQuery)->with(['user', 'pasti', 'kpiSnapshot']);
        $query->where('is_assistant', $activeTab === 'assistant');

        return view('gurus.index', [
            'gurus' => $query->latest()->paginate(10)->withQueryString(),
            'activeTab' => $activeTab,
            'guruCount' => (clone $scopeQuery)->where('is_assistant', false)->count(),
            'assistantCount' => (clone $scopeQuery)->where('is_assistant', true)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $pastis = $this->pastisForUser($user);

        return view('gurus.form', [
            'guru' => new Guru(),
            'userModel' => new User(),
            'pastis' => $pastis,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $isAssistant = $request->boolean('is_assistant');
        $emailRules = ['email', 'max:255'];
        $passwordRules = ['string', 'min:8', 'confirmed'];

        if ($isAssistant) {
            $emailRules[] = 'nullable';
            $passwordRules[] = 'nullable';
        } else {
            $emailRules[] = 'unique:users,email';
            $emailRules[] = 'required';
            $passwordRules[] = 'required';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'is_assistant' => ['nullable', 'boolean'],
            'email' => $emailRules,
            'password' => $passwordRules,
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'terima_anugerah' => ['nullable', 'boolean'],
        ]);

        $this->ensurePastiAllowed($user, $data['pasti_id'] ?? null);

        $guruUser = null;
        if (! $isAssistant) {
            $guruUser = User::query()->create([
                'name' => $data['name'],
                'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'locale' => 'ms',
            ]);

            $guruUser->syncRoles(['guru']);
        }

        $guru = Guru::query()->create([
            'user_id' => $guruUser?->id,
            'pasti_id' => $data['pasti_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'avatar_path' => null,
            'is_assistant' => $isAssistant,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
            'terima_anugerah' => (bool) ($data['terima_anugerah'] ?? false),
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($isAssistant) {
                $guru->update(['avatar_path' => $avatarPath]);
            } else {
                $guruUser?->update(['avatar_path' => $avatarPath]);
            }
        }

        $this->kpiCalculationService->recalculateForGuru($guru);

        return redirect()->route('users.gurus.index')->with('status', __('messages.saved'));
    }

    public function edit(Request $request, Guru $users_guru): View
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);

        return view('gurus.form', [
            'guru' => $users_guru,
            'userModel' => $users_guru->user,
            'pastis' => $this->pastisForUser($user),
        ]);
    }

    public function update(Request $request, Guru $users_guru): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);

        $isAssistant = $request->boolean('is_assistant');
        $emailRules = ['email', 'max:255'];
        $passwordRules = ['string', 'min:8', 'confirmed'];

        if ($isAssistant) {
            $emailRules[] = 'nullable';
            $passwordRules[] = 'nullable';
        } else {
            $emailRules[] = Rule::unique('users', 'email')->ignore($users_guru->user_id);
            $emailRules[] = 'required';
            $passwordRules[] = $users_guru->user_id === null ? 'required' : 'nullable';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'is_assistant' => ['nullable', 'boolean'],
            'email' => $emailRules,
            'password' => $passwordRules,
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joined_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'terima_anugerah' => ['nullable', 'boolean'],
        ]);

        $this->ensurePastiAllowed($user, $data['pasti_id'] ?? null);

        $existingGuruAvatar = $users_guru->avatar_path;

        if ($isAssistant) {
            if ($users_guru->user_id) {
                $formerUser = $users_guru->user;
                $users_guru->user_id = null;
                if (! $users_guru->avatar_path && $formerUser?->avatar_path) {
                    $users_guru->avatar_path = $formerUser->avatar_path;
                }
                $users_guru->save();
                $formerUser?->delete();
            }
        } else {
            if ($users_guru->user_id) {
                $users_guru->user?->update([
                    'name' => $data['name'],
                    'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                    'email' => $data['email'],
                    ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
                ]);
            } else {
                $newGuruUser = User::query()->create([
                    'name' => $data['name'],
                    'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'locale' => 'ms',
                ]);
                $newGuruUser->syncRoles(['guru']);
                if ($existingGuruAvatar) {
                    $newGuruUser->avatar_path = $existingGuruAvatar;
                    $newGuruUser->save();
                    $users_guru->avatar_path = null;
                }
                $users_guru->user_id = $newGuruUser->id;
                $users_guru->save();
            }
        }

        $users_guru->update([
            'pasti_id' => $data['pasti_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'is_assistant' => $isAssistant,
            'avatar_path' => $isAssistant ? $users_guru->avatar_path : null,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
            'terima_anugerah' => (bool) ($data['terima_anugerah'] ?? false),
        ]);

        if ($request->boolean('remove_avatar')) {
            if ($isAssistant) {
                if ($users_guru->avatar_path) {
                    Storage::disk('public')->delete($users_guru->avatar_path);
                    $users_guru->update(['avatar_path' => null]);
                }
            } else {
                $currentUser = $users_guru->user;
                if ($currentUser?->avatar_path) {
                    Storage::disk('public')->delete($currentUser->avatar_path);
                    $currentUser->update(['avatar_path' => null]);
                }
            }
        }

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($isAssistant) {
                if ($users_guru->avatar_path) {
                    Storage::disk('public')->delete($users_guru->avatar_path);
                }
                $users_guru->update(['avatar_path' => $newAvatarPath]);
            } else {
                $currentUser = $users_guru->user;
                if ($currentUser?->avatar_path) {
                    Storage::disk('public')->delete($currentUser->avatar_path);
                }
                $currentUser?->update(['avatar_path' => $newAvatarPath]);
            }
        }

        $this->kpiCalculationService->recalculateForGuru($users_guru);

        return redirect()->route('users.gurus.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Guru $users_guru): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);

        if ($users_guru->avatar_path) {
            Storage::disk('public')->delete($users_guru->avatar_path);
        }

        if ($users_guru->user_id) {
            if ($users_guru->user?->avatar_path) {
                Storage::disk('public')->delete($users_guru->user->avatar_path);
            }
            $users_guru->user?->delete();
        } else {
            $users_guru->delete();
        }

        return redirect()->route('users.gurus.index')->with('status', __('messages.deleted'));
    }

    private function pastisForUser(User $user)
    {
        if ($this->isMasterAdmin($user)) {
            return Pasti::query()->orderBy('name')->get();
        }

        return $user->assignedPastis()->orderBy('name')->get();
    }

    private function ensurePastiAllowed(User $user, ?int $pastiId): void
    {
        if ($pastiId === null || $this->isMasterAdmin($user)) {
            return;
        }

        abort_unless(in_array($pastiId, $this->assignedPastiIds($user), true), 403);
    }

    private function ensureGuruAllowed(User $user, Guru $guru): void
    {
        if ($this->isMasterAdmin($user)) {
            return;
        }

        abort_unless(in_array((int) $guru->pasti_id, $this->assignedPastiIds($user), true), 403);
    }
}
