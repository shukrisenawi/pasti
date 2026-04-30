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
            'kad_pengenalan' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    $existingUser = User::where('email', $value)->first();
                    if ($existingUser && ($existingUser->hasRole('admin') || $existingUser->hasRole('master_admin'))) {
                        $fail('E-mel ini telah digunakan oleh pentadbir lain.');
                    }
                }
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
            'assignment_scope' => ['required', 'in:all,selected'],
        ]);

        $admin = User::query()->where('email', $data['email'])->first();

        if ($admin) {
            $admin->update([
                'name' => $data['name'],
                'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                'tarikh_lahir' => $data['tarikh_lahir'],
                'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                'password' => Hash::make($data['password']),
                'admin_assignment_scope' => $data['assignment_scope'],
            ]);
        } else {
            $admin = User::query()->create([
                'name' => $data['name'],
                'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                'tarikh_lahir' => $data['tarikh_lahir'],
                'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'locale' => 'ms',
                'admin_assignment_scope' => $data['assignment_scope'],
            ]);
        }

        $isGuru = false;
        $admin->syncRoles(['admin']);
        
        if (($data['assignment_scope'] ?? 'selected') === 'all') {
            $allPastiIds = Pasti::pluck('id')->all();
            $admin->assignedPastis()->sync($allPastiIds);
        } else {
            $admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        }

        if ($admin->guru) {
            $admin->guru->update([
                'kad_pengenalan' => $data['kad_pengenalan'] ?? null,
            ]);
        }

        $this->syncAdminGuruProfile($admin, $isGuru, null, null);

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
            'kad_pengenalan' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) use ($users_admin) {
                    $existingUser = User::where('email', $value)->where('id', '<>', $users_admin->id)->first();
                    if ($existingUser && ($existingUser->hasRole('admin') || $existingUser->hasRole('master_admin'))) {
                        $fail('E-mel ini telah digunakan oleh pentadbir lain.');
                    }
                }
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
            'assignment_scope' => ['required', 'in:all,selected'],
        ]);

        $existingUserWithEmail = User::where('email', $data['email'])->where('id', '<>', $users_admin->id)->first();

        if ($existingUserWithEmail) {
            // Merge this admin record into the existing user record
            $oldUser = $users_admin;
            $users_admin = $existingUserWithEmail;

            $users_admin->update([
                'name' => $data['name'],
                'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
                'tarikh_lahir' => $data['tarikh_lahir'],
                'tarikh_exp_skim_pas' => $data['tarikh_exp_skim_pas'],
                'admin_assignment_scope' => $data['assignment_scope'],
                ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            // Copy over relations if necessary? Actually roles and assignedPastis will be handled below.
            
            if ($oldUser->id !== $users_admin->id) {
                // If we're merging from a different record, we might need to handle linked data.
                // But for simplicity, we'll just delete the old one.
                $oldUser->delete();
            }
        } else {
            $users_admin->name = $data['name'];
            $users_admin->nama_samaran = $data['nama_samaran'] ?? $data['name'];
            $users_admin->tarikh_lahir = $data['tarikh_lahir'];
            $users_admin->tarikh_exp_skim_pas = $data['tarikh_exp_skim_pas'];
            $users_admin->email = $data['email'];
            $users_admin->admin_assignment_scope = $data['assignment_scope'];

            if (! empty($data['password'])) {
                $users_admin->password = Hash::make($data['password']);
            }

            $users_admin->save();
        }
        $isGuru = false;
        $users_admin->syncRoles(['admin']);

        if (($data['assignment_scope'] ?? 'selected') === 'all') {
            $allPastiIds = Pasti::pluck('id')->all();
            $users_admin->assignedPastis()->sync($allPastiIds);
        } else {
            $users_admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        }

        if ($users_admin->guru) {
            $users_admin->guru->update([
                'kad_pengenalan' => $data['kad_pengenalan'] ?? null,
            ]);
        }

        $this->syncAdminGuruProfile($users_admin, $isGuru, null, null);

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
