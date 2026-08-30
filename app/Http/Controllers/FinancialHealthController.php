<?php

namespace App\Http\Controllers;

use App\Domain\Reports\Actions\HealthScoreCalculator;

class FinancialHealthController extends Controller
{
    public function __invoke()
    {
        return view('financial-health.index', [
            'report' => (new HealthScoreCalculator())->calculate(),
        ]);
    }
}
