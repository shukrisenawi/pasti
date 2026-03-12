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
        ]);
    }

    public function create(): View
    {
        return view('admin-users.form', [
            'adminUser' => new User(),
            'pastis' => Pasti::query()->orderBy('name')->get(),
            'selectedPastis' => [],
            'isGuru' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_guru' => ['nullable', 'boolean'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
        ]);

        $admin = User::query()->create([
            'name' => $data['name'],
            'nama_samaran' => $data['nama_samaran'] ?? $data['name'],
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
        $admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        $this->syncAdminGuruProfile($admin, $isGuru, $data['pasti_ids'] ?? []);

        return redirect()->route('users.admins.index')->with('status', __('messages.saved'));
    }

    public function edit(User $users_admin): View
    {
        return view('admin-users.form', [
            'adminUser' => $users_admin,
            'pastis' => Pasti::query()->orderBy('name')->get(),
            'selectedPastis' => $users_admin->assignedPastis()->pluck('pastis.id')->all(),
            'isGuru' => $users_admin->hasRole('guru'),
        ]);
    }

    public function update(Request $request, User $users_admin): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_samaran' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($users_admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_guru' => ['nullable', 'boolean'],
            'pasti_ids' => ['array'],
            'pasti_ids.*' => ['integer', 'exists:pastis,id'],
        ]);

        $users_admin->name = $data['name'];
        $users_admin->nama_samaran = $data['nama_samaran'] ?? $data['name'];
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
        $users_admin->assignedPastis()->sync($data['pasti_ids'] ?? []);
        $this->syncAdminGuruProfile($users_admin, $isGuru, $data['pasti_ids'] ?? []);

        return redirect()->route('users.admins.index')->with('status', __('messages.saved'));
    }

    public function destroy(User $users_admin): RedirectResponse
    {
        $users_admin->delete();

        return redirect()->route('users.admins.index')->with('status', __('messages.deleted'));
    }

    private function syncAdminGuruProfile(User $admin, bool $isGuru, array $pastiIds): void
    {
        if ($isGuru) {
            Guru::query()->firstOrCreate(
                ['user_id' => $admin->id],
                [
                    'pasti_id' => $pastiIds[0] ?? null,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'is_assistant' => false,
                    'active' => true,
                ],
            );

            return;
        }

        $admin->guru()?->update(['active' => false]);
    }
}
