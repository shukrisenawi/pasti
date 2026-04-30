<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Pasti;
use App\Models\User;
use App\Services\KpiCalculationService;
use App\Support\GuruProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuruController extends Controller
{
    private const TEST_GURU_EMAIL = 'test@pasti';

    public function __construct(private readonly KpiCalculationService $kpiCalculationService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $activeTab = $request->query('tab', 'guru');
        if (! in_array($activeTab, ['guru', 'assistant', 'inactive'], true)) {
            $activeTab = 'guru';
        }
        $search = trim((string) $request->query('search', ''));

        $scopeQuery = Guru::query();

        if ($user->hasRole('admin')) {
            $scopeQuery->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        $activeScope = (clone $scopeQuery)->whereNotNull('pasti_id');

        if ($activeTab === 'inactive') {
            $query = Guru::query()->whereNull('pasti_id');
        } else {
            $query = (clone $activeScope)->where('is_assistant', $activeTab === 'assistant');
        }

        $query->with(['user', 'pasti', 'kpiSnapshot'])
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->leftJoin('users', 'users.id', '=', 'gurus.user_id')
            ->select('gurus.*');

        $query->when($search !== '', function ($builder) use ($search): void {
            $keyword = '%' . $search . '%';

            $builder->where(function ($q) use ($keyword): void {
                $q->where('gurus.name', 'like', $keyword)
                    ->orWhere('gurus.email', 'like', $keyword)
                    ->orWhere('pastis.name', 'like', $keyword);
            });
        });

        return view('gurus.index', [
            'gurus' => $query
                ->orderByRaw(
                    "CASE WHEN COALESCE(users.email, gurus.email, '') = ? THEN 0 ELSE 1 END",
                    [self::TEST_GURU_EMAIL]
                )
                ->when(
                    $activeTab === 'inactive',
                    fn ($builder) => $builder
                        ->orderByDesc('gurus.created_at')
                        ->orderByDesc('gurus.id'),
                    fn ($builder) => $builder
                        ->orderByRaw("CASE WHEN pastis.name IS NULL OR pastis.name = '' THEN 1 ELSE 0 END")
                        ->orderBy('pastis.name')
                        ->orderBy('gurus.name')
                )
                ->paginate(9)
                ->withQueryString(),
            'activeTab' => $activeTab,
            'search' => $search,
            'guruCount' => (clone $activeScope)->where('is_assistant', false)->count(),
            'assistantCount' => (clone $activeScope)->where('is_assistant', true)->count(),
            'inactiveCount' => Guru::query()->whereNull('pasti_id')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
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

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $isAssistant = $request->boolean('is_assistant');
        $emailRules = ['email', 'max:255'];
        $passwordRules = ['nullable', 'string', 'min:8', 'confirmed'];
        $avatarRules = ['image', 'mimes:jpg,jpeg,png,webp', 'max:7168'];
        $kadPengenalanRules = ['required', 'string', 'max:30'];
        $assistantAllowanceRules = ['nullable', 'numeric', 'min:0'];

        if ($isAssistant) {
            $emailRules[] = 'nullable';
            $passwordRules[] = 'nullable';
            array_unshift($avatarRules, 'required');
        } else {
            $emailRules[] = 'required';
            $emailRules[] = function ($attribute, $value, $fail) {
                $existingUser = User::where('email', $value)->first();
                if ($existingUser && $existingUser->hasRole('guru')) {
                    $fail('E-mel ini telah digunakan oleh guru lain.');
                }
            };
            $passwordRules[] = 'nullable';
            array_unshift($avatarRules, 'nullable');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'is_assistant' => ['nullable', 'boolean'],
            'email' => $emailRules,
            'password' => $passwordRules,
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kad_pengenalan' => $kadPengenalanRules,
            'elaun' => $assistantAllowanceRules,
            'elaun_transit' => $assistantAllowanceRules,
            'elaun_lain' => $assistantAllowanceRules,
            'joined_at' => ['nullable', 'date'],
            'tarikh_lahir' => ['nullable', 'date'],
            'tarikh_exp_skim_pas' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'kursus_guru' => ['nullable', 'string', Rule::in(Guru::KURSUS_GURU_OPTIONS)],
            'marital_status' => ['nullable', 'string', 'in:single,married,widowed,divorced'],
        ]);

        $this->ensurePastiAllowed($user, $data['pasti_id'] ?? null);

        $guruUser = null;
        if (! $isAssistant) {
            $guruUser = User::query()->where('email', $data['email'])->first();

            if ($guruUser) {
                $guruUser->update([
                    'name' => $data['name'],
                    ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
                ]);
            } else {
                $guruUser = User::query()->create([
                    'name' => $data['name'],
                    'nama_samaran' => null,
                    'email' => $data['email'],
                    'tarikh_lahir' => null,
                    'tarikh_exp_skim_pas' => null,
                    'avatar_path' => null,
                    'password' => Hash::make($data['password'] ?: GuruProfileCompletionService::DEFAULT_GURU_PASSWORD),
                    'locale' => 'ms',
                    'force_password_change' => true,
                ]);
            }

            $guruUser->assignRole('guru');
        }

        $guru = Guru::query()->create([
            'user_id' => $guruUser?->id,
            'pasti_id' => null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'avatar_path' => null,
            'kad_pengenalan' => $data['kad_pengenalan'] ?? null,
            'elaun' => $isAssistant ? ($data['elaun'] ?? null) : null,
            'elaun_transit' => $isAssistant ? ($data['elaun_transit'] ?? null) : null,
            'elaun_lain' => $isAssistant ? ($data['elaun_lain'] ?? null) : null,
            'is_assistant' => $isAssistant,
            'phone' => null,
            'joined_at' => null,
            'active' => (bool) ($data['active'] ?? false),
            'kursus_guru' => $data['kursus_guru'] ?? null,
            'terima_anugerah' => ($data['kursus_guru'] ?? null) === 'terima_anugerah',
            'marital_status' => null,
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

        if ($user->isOperatingAsGuru()) {
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

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);

        $isAssistant = $request->boolean('is_assistant');
        $emailRules = ['email', 'max:255'];
        $passwordRules = ['nullable', 'string', 'min:8', 'confirmed'];
        $kadPengenalanRules = ['required', 'string', 'max:30'];
        $assistantAllowanceRules = ['nullable', 'numeric', 'min:0'];

        if ($isAssistant) {
            $emailRules[] = 'nullable';
            $passwordRules[] = 'nullable';
        } else {
            $emailRules[] = 'required';
            $emailRules[] = function ($attribute, $value, $fail) use ($users_guru) {
                $existingUser = User::where('email', $value)->where('id', '<>', $users_guru->user_id)->first();
                if ($existingUser && $existingUser->hasRole('guru')) {
                    $fail('E-mel ini telah digunakan oleh guru lain.');
                }
            };
            $passwordRules[] = 'nullable';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'is_assistant' => ['nullable', 'boolean'],
            'email' => $emailRules,
            'password' => $passwordRules,
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
            'remove_avatar' => ['nullable', 'boolean'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kad_pengenalan' => $kadPengenalanRules,
            'elaun' => $assistantAllowanceRules,
            'elaun_transit' => $assistantAllowanceRules,
            'elaun_lain' => $assistantAllowanceRules,
            'joined_at' => ['nullable', 'date'],
            'tarikh_lahir' => ['nullable', 'date'],
            'tarikh_exp_skim_pas' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'kursus_guru' => ['nullable', 'string', Rule::in(Guru::KURSUS_GURU_OPTIONS)],
            'marital_status' => ['nullable', 'string', 'in:single,married,widowed,divorced'],
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
            $existingUserWithEmail = User::where('email', $data['email'])->where('id', '<>', $users_guru->user_id)->first();

            if ($existingUserWithEmail) {
                // If there was a previous separate user for this guru, delete it
                $oldGuruUser = $users_guru->user;
                
                $users_guru->user_id = $existingUserWithEmail->id;
                $users_guru->save();

                $existingUserWithEmail->update([
                    'name' => $data['name'],
                    'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                    'tarikh_lahir' => $data['tarikh_lahir'],
                    'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                    ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
                ]);
                $existingUserWithEmail->assignRole('guru');

                if ($oldGuruUser && $oldGuruUser->id !== $existingUserWithEmail->id) {
                    $oldGuruUser->delete();
                }
            } elseif ($users_guru->user_id) {
                $users_guru->user?->update([
                    'name' => $data['name'],
                    'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                    'email' => $data['email'],
                    'tarikh_lahir' => $data['tarikh_lahir'],
                    'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                    ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
                ]);
            } else {
                $newGuruUser = User::query()->create([
                    'name' => $data['name'],
                    'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                    'email' => $data['email'],
                    'tarikh_lahir' => $data['tarikh_lahir'],
                    'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                    'password' => Hash::make($data['password'] ?: GuruProfileCompletionService::DEFAULT_GURU_PASSWORD),
                    'locale' => 'ms',
                    'force_password_change' => true,
                ]);
                $newGuruUser->assignRole('guru');
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
            'kad_pengenalan' => $data['kad_pengenalan'] ?? null,
            'elaun' => $isAssistant ? ($data['elaun'] ?? null) : null,
            'elaun_transit' => $isAssistant ? ($data['elaun_transit'] ?? null) : null,
            'elaun_lain' => $isAssistant ? ($data['elaun_lain'] ?? null) : null,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
            'kursus_guru' => $data['kursus_guru'] ?? null,
            'terima_anugerah' => ($data['kursus_guru'] ?? null) === 'terima_anugerah',
            'marital_status' => $data['marital_status'] ?? null,
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

        if ($user->isOperatingAsGuru()) {
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

    public function resetPassword(Request $request, Guru $users_guru): RedirectResponse
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);

        $targetUser = $users_guru->user;
        abort_unless($targetUser && $targetUser->hasRole('guru'), 404);

        $targetUser->update([
            'password' => Hash::make(GuruProfileCompletionService::DEFAULT_GURU_PASSWORD),
            'force_password_change' => true,
        ]);

        return redirect()
            ->route('users.gurus.index', $request->query())
            ->with('status', 'Kata laluan guru berjaya direset kepada 123.');
    }

    public function assistants(Request $request, Guru $users_guru): View
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);
        abort_if($users_guru->is_assistant, 404);

        $tab = $request->query('tab', 'list');
        if (! in_array($tab, ['list', 'add'], true)) {
            $tab = 'list';
        }

        $assistants = Guru::query()
            ->with(['pasti', 'user'])
            ->where('is_assistant', true)
            ->where('pasti_id', $users_guru->pasti_id)
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('gurus.assistants', [
            'guru' => $users_guru,
            'assistants' => $assistants,
            'tab' => $tab,
            'isSelfMode' => false,
        ]);
    }

    public function storeAssistant(Request $request, Guru $users_guru): RedirectResponse
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
            abort(403);
        }

        $this->ensureGuruAllowed($user, $users_guru);
        abort_if($users_guru->is_assistant, 404);

        if (! $users_guru->pasti_id) {
            return redirect()
                ->route('users.gurus.assistants', ['users_guru' => $users_guru, 'tab' => 'add'])
                ->withErrors(['assistant' => 'Guru utama ini belum ada PASTI. Sila kemaskini PASTI dahulu.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kad_pengenalan' => ['required', 'string', 'max:30'],
            'elaun' => ['nullable', 'numeric', 'min:0'],
            'elaun_transit' => ['nullable', 'numeric', 'min:0'],
            'elaun_lain' => ['nullable', 'numeric', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
        ]);

        $assistant = Guru::query()->create([
            'user_id' => null,
            'pasti_id' => $users_guru->pasti_id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'kad_pengenalan' => $data['kad_pengenalan'],
            'elaun' => $data['elaun'] ?? null,
            'elaun_transit' => $data['elaun_transit'] ?? null,
            'elaun_lain' => $data['elaun_lain'] ?? null,
            'is_assistant' => true,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        if ($request->hasFile('avatar')) {
            $assistant->update([
                'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
            ]);
        }

        $this->kpiCalculationService->recalculateForGuru($assistant);

        return redirect()
            ->route('users.gurus.assistants', ['users_guru' => $users_guru, 'tab' => 'list'])
            ->with('status', 'Pembantu guru berjaya ditambah.');
    }

    public function assistantsMine(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);

        $guru = $user->guru;
        abort_unless($guru, 403);

        $tab = $request->query('tab', 'list');
        if (! in_array($tab, ['list', 'add'], true)) {
            $tab = 'list';
        }

        $assistants = Guru::query()
            ->where('is_assistant', true)
            ->where('pasti_id', $guru->pasti_id)
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('gurus.assistants', [
            'guru' => $guru,
            'assistants' => $assistants,
            'tab' => $tab,
            'isSelfMode' => true,
        ]);
    }

    public function storeAssistantMine(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);

        $guru = $user->guru;
        abort_unless($guru, 403);

        if (! $guru->pasti_id) {
            return redirect()
                ->route('guru-assistants.index', ['tab' => 'add'])
                ->withErrors(['assistant' => 'PASTI anda belum ditetapkan.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kad_pengenalan' => ['required', 'string', 'max:30'],
            'elaun' => ['nullable', 'numeric', 'min:0'],
            'elaun_transit' => ['nullable', 'numeric', 'min:0'],
            'elaun_lain' => ['nullable', 'numeric', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
        ]);

        $assistant = Guru::query()->create([
            'user_id' => null,
            'pasti_id' => $guru->pasti_id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'kad_pengenalan' => $data['kad_pengenalan'],
            'elaun' => $data['elaun'] ?? null,
            'elaun_transit' => $data['elaun_transit'] ?? null,
            'elaun_lain' => $data['elaun_lain'] ?? null,
            'is_assistant' => true,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        if ($request->hasFile('avatar')) {
            $assistant->update([
                'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
            ]);
        }

        $this->kpiCalculationService->recalculateForGuru($assistant);

        return redirect()->route('guru-assistants.index', ['tab' => 'list'])->with('status', 'Pembantu guru berjaya ditambah.');
    }

    public function editAssistantMine(Request $request, Guru $assistant): View
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        $this->ensureOwnedAssistant($user, $assistant);

        return view('gurus.assistant-form', [
            'assistant' => $assistant,
        ]);
    }

    public function updateAssistantMine(Request $request, Guru $assistant): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        $this->ensureOwnedAssistant($user, $assistant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kad_pengenalan' => ['required', 'string', 'max:30'],
            'elaun' => ['nullable', 'numeric', 'min:0'],
            'elaun_transit' => ['nullable', 'numeric', 'min:0'],
            'elaun_lain' => ['nullable', 'numeric', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $assistant->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'kad_pengenalan' => $data['kad_pengenalan'],
            'elaun' => $data['elaun'] ?? null,
            'elaun_transit' => $data['elaun_transit'] ?? null,
            'elaun_lain' => $data['elaun_lain'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        if ($request->boolean('remove_avatar') && $assistant->avatar_path) {
            Storage::disk('public')->delete($assistant->avatar_path);
            $assistant->update(['avatar_path' => null]);
        }

        if ($request->hasFile('avatar')) {
            if ($assistant->avatar_path) {
                Storage::disk('public')->delete($assistant->avatar_path);
            }

            $assistant->update([
                'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
            ]);
        }

        $this->kpiCalculationService->recalculateForGuru($assistant);

        return redirect()->route('guru-assistants.index', ['tab' => 'list'])->with('status', __('messages.saved'));
    }

    public function destroyAssistantMine(Request $request, Guru $assistant): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        $this->ensureOwnedAssistant($user, $assistant);

        if ($assistant->avatar_path) {
            Storage::disk('public')->delete($assistant->avatar_path);
        }

        $assistant->delete();

        return redirect()->route('guru-assistants.index', ['tab' => 'list'])->with('status', __('messages.deleted'));
    }

    public function directory(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $gurus = Guru::query()
            ->with(['user', 'pasti'])
            ->where('is_assistant', false)
            ->whereNotNull('pasti_id')
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->leftJoin('users', 'users.id', '=', 'gurus.user_id')
            ->select('gurus.*')
            ->when($user->isOperatingAsGuru(), function ($query): void {
                $query->whereRaw(
                    "COALESCE(users.email, gurus.email, '') <> ?",
                    [self::TEST_GURU_EMAIL]
                );
            })
            ->orderByRaw("CASE WHEN pastis.name IS NULL OR pastis.name = '' THEN 1 ELSE 0 END")
            ->orderBy('pastis.name')
            ->orderBy('gurus.name')
            ->paginate(9);

        return view('guru-directory.index', [
            'gurus' => $gurus,
        ]);
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

    private function ensureOwnedAssistant(User $user, Guru $assistant): void
    {
        abort_unless($assistant->is_assistant, 404);

        $userPastiId = (int) ($user->guru?->pasti_id ?? 0);
        abort_unless($userPastiId > 0 && $userPastiId === (int) $assistant->pasti_id, 403);
    }
}
