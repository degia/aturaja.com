<?php

namespace App\Livewire;

use App\Domain\Reports\Actions\HealthScoreCalculator;
use Livewire\Component;

class FinancialHealth extends Component
{
    public function render()
    {
        return view('livewire.financial-health', [
            'report' => (new HealthScoreCalculator())->calculate(),
        ]);
    }
}
