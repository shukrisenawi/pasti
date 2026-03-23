<?php

namespace Database\Seeders;

use App\Models\FinancialTransactionType;
use Illuminate\Database\Seeder;

class FinancialTransactionTypeSeeder extends Seeder
{
    public function run(): void
    {
        FinancialTransactionType::query()->updateOrCreate(
            ['name' => 'Claim'],
            [
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}

