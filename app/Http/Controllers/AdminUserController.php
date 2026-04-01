<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin-users.index', [
            'admins' => User::query()->role('admin')->with('assignedPastis')->latest()->paginate(10),
            'pastiCount' => Pasti::count(),
        ]);
    }

    public function create(): View
    {
        return view('admin-users.form', [
            'adminUser' => new User(),
            'pastis' => Pasti::query()->orderBy('name')->get(),
            'selectedPastis' => [],
            'isGuru' => false,
            'guruPastiId' => null,
            'pastiCount' => Pasti::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'tarikh_lahir' => ['nullable', 'date'],
            'tarikh_exp_skim_pas' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_guru' => ['nullable', 'boolean'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
            'assignment_scope' => ['required', 'in:all,selected'],
            'marital_status' => ['nullable', 'string', 'in:single,married,widowed,divorced'],
        ]);

        $admin = User::query()->create([
            'name' => $data['name'],
            'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
            'tarikh_lahir' => $data['tarikh_lahir'],
            'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'locale' => 'ms',
        ]);

        $isGuru = (bool) ($data['is_guru'] ?? false);
        $roles = ['admin'];
        if ($isGuru) {
            $roles[] = 'guru';
        }

        $admin->syncRoles($roles);
        
        if (($data['assignment_scope'] ?? 'selected') === 'all') {
            $allPastiIds = Pasti::pluck('id')->all();
            $admin->assignedPastis()->sync($allPastiIds);
        } else {
            $admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        }

        $this->syncAdminGuruProfile($admin, $isGuru, $data['pasti_id'] ?? null, $data['marital_status'] ?? null);

        return redirect()->route('users.admins.index')->with('status', __('messages.saved'));
    }

    public function edit(User $users_admin): View
    {
        return view('admin-users.form', [
            'adminUser' => $users_admin,
            'pastis' => Pasti::query()->orderBy('name')->get(),
            'selectedPastis' => $users_admin->assignedPastis()->pluck('pastis.id')->all(),
            'isGuru' => $users_admin->hasRole('guru'),
            'guruPastiId' => $users_admin->guru?->pasti_id,
            'pastiCount' => Pasti::count(),
        ]);
    }

    public function update(Request $request, User $users_admin): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'tarikh_lahir' => ['nullable', 'date'],
            'tarikh_exp_skim_pas' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($users_admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_guru' => ['nullable', 'boolean'],
            'pasti_id' => ['nullable', 'integer', 'exists:pastis,id'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
            'assignment_scope' => ['required', 'in:all,selected'],
            'marital_status' => ['nullable', 'string', 'in:single,married,widowed,divorced'],
        ]);

        $users_admin->name = $data['name'];
        $users_admin->nama_samaran = $data['nama_samaran'] ?? $data['name'];
        $users_admin->tarikh_lahir = $data['tarikh_lahir'];
        $users_admin->tarikh_exp_skim_pas = $data['tarikh_exp_skim_pas'];
        $users_admin->email = $data['email'];

        if (! empty($data['password'])) {
            $users_admin->password = Hash::make($data['password']);
        }

        $users_admin->save();
        $isGuru = (bool) ($data['is_guru'] ?? false);
        $roles = ['admin'];
        if ($isGuru) {
            $roles[] = 'guru';
        }

        $users_admin->syncRoles($roles);

        if (($data['assignment_scope'] ?? 'selected') === 'all') {
            $allPastiIds = Pasti::pluck('id')->all();
            $users_admin->assignedPastis()->sync($allPastiIds);
        } else {
            $users_admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        }

        $this->syncAdminGuruProfile($users_admin, $isGuru, $data['pasti_id'] ?? null, $data['marital_status'] ?? null);

        return redirect()->route('users.admins.index')->with('status', __('messages.saved'));
    }

    public function destroy(User $users_admin): RedirectResponse
    {
        $users_admin->delete();

        return redirect()->route('users.admins.index')->with('status', __('messages.deleted'));
    }

    public function expiredSkimPas(): View
    {
        $expiredUsers = User::query()
            ->where('tarikh_exp_skim_pas', '<', now()->startOfDay())
            ->latest()
            ->paginate(20);

        return view('admin-users.expired-skim-pas', [
            'users' => $expiredUsers,
        ]);
    }

    private function syncAdminGuruProfile(User $admin, bool $isGuru, ?int $pastiId, ?string $maritalStatus = null): void
    {
        if ($isGuru) {
            Guru::query()->updateOrCreate(
                ['user_id' => $admin->id],
                [
                    'pasti_id' => $pastiId,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'marital_status' => $maritalStatus,
                    'is_assistant' => false,
                    'active' => true,
                ],
            );

            return;
        }

        $admin->guru()?->update(['active' => false]);
    }
}
