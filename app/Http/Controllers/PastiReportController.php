<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Pasti;
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

        $activeTab = request()->query('tab', 'maklumat-pasti');
        if (! in_array($activeTab, ['maklumat-pasti', 'elaun-guru'], true)) {
            $activeTab = 'maklumat-pasti';
        }

        $reports = Guru::query()
            ->where('is_assistant', false)
            ->whereRaw('lower(coalesce(gurus.name, \'\')) <> ?', ['test'])
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

        $pastiReports = Pasti::query()
            ->with('latestCompletedInformationRequest')
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('id', $user->assignedPastis()->pluck('pastis.id'))
            )
            ->whereHas('latestCompletedInformationRequest')
            ->orderBy('name')
            ->paginate(20, ['*'], 'pastiPage')
            ->through(function (Pasti $pasti): Pasti {
                $latestCompleted = $pasti->latestCompletedInformationRequest;
                $pasti->maklumat_pasti_jumlah = (int) (
                    ($latestCompleted?->murid_lelaki_4_tahun ?? 0)
                    + ($latestCompleted?->murid_perempuan_4_tahun ?? 0)
                    + ($latestCompleted?->murid_lelaki_5_tahun ?? 0)
                    + ($latestCompleted?->murid_perempuan_5_tahun ?? 0)
                    + ($latestCompleted?->murid_lelaki_6_tahun ?? 0)
                    + ($latestCompleted?->murid_perempuan_6_tahun ?? 0)
                );

                return $pasti;
            })
            ->withQueryString();

        return view('pasti-reports.index', [
            'reports' => $reports,
            'pastiReports' => $pastiReports,
            'activeTab' => $activeTab,
        ]);
    }
}
