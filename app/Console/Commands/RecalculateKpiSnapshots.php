<?php

namespace App\Console\Commands;

use App\Services\KpiCalculationService;
use Illuminate\Console\Command;

class RecalculateKpiSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-kpi-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate KPI snapshots for all gurus';

    /**
     * Execute the console command.
     */
    public function handle(KpiCalculationService $kpiCalculationService): int
    {
        $kpiCalculationService->recalculateAll();
        $this->info('KPI snapshots recalculated successfully.');

        return self::SUCCESS;
    }
}
