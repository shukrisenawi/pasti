<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramTitleOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramTitleOptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('program_title_options', 'title')],
            'markah' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $maxSortOrder = (int) ProgramTitleOption::query()->max('sort_order');

        ProgramTitleOption::query()->create([
            'title' => $data['title'],
            'markah' => $data['markah'],
            'sort_order' => $maxSortOrder + 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return back()
            ->with('status', __('messages.saved'))
            ->with('program_title_options_tab', true);
    }

    public function update(Request $request, ProgramTitleOption $programTitleOption): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('program_title_options', 'title')->ignore($programTitleOption->id)],
            'markah' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $programTitleOption->update($data);

        return back()
            ->with('status', __('messages.saved'))
            ->with('program_title_options_tab', true);
    }

    public function destroy(Request $request, ProgramTitleOption $programTitleOption): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        if (Program::query()->where('title', $programTitleOption->title)->exists()) {
            return back()
                ->withErrors(['program_title_option' => 'Pilihan tajuk ini sudah digunakan dalam rekod program.'])
                ->with('program_title_options_tab', true);
        }

        $programTitleOption->delete();

        return back()
            ->with('status', __('messages.deleted'))
            ->with('program_title_options_tab', true);
    }
}
