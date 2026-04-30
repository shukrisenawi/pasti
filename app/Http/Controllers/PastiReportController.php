<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class PastiReportController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $reports = Guru::query()
            ->where('is_assistant', false)
            ->with(['pasti', 'latestCompletedSalaryRequest'])
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('pasti_id', $user->assignedPastis()->pluck('pastis.id'))
            )
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->select('gurus.*')
            ->orderByRaw("CASE WHEN pastis.name IS NULL OR pastis.name = '' THEN 1 ELSE 0 END")
            ->orderBy('pastis.name')
            ->orderBy('gurus.name')
            ->paginate(20)
            ->withQueryString();

        return view('pasti-reports.index', [
            'reports' => $reports,
        ]);
    }
}
