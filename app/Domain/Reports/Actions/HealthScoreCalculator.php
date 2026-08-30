<?php

namespace App\Domain\Reports\Actions;

use App\Models\Account;
use App\Models\Liability;
use App\Models\Transaction;

class HealthScoreCalculator
{
    public const IDEAL_SAVINGS_RATE = 20.0;
    public const IDEAL_DTI = 30.0;
    public const EMERGENCY_MIN_MONTHS = 3.0;
    public const EMERGENCY_MAX_MONTHS = 6.0;

    public function calculate(?string $referenceDate = null): array
    {
        $reference = $referenceDate ? now()->parse($referenceDate) : now();
        $income = (float) Transaction::where('type', 'income')
            ->whereYear('transaction_date', $reference->year)
            ->whereMonth('transaction_date', $reference->month)
            ->sum('amount');
        $expense = (float) Transaction::where('type', 'expense')
            ->whereYear('transaction_date', $reference->year)
            ->whereMonth('transaction_date', $reference->month)
            ->sum('amount');

        $monthlyInstallments = (float) Liability::sum('monthly_installment');

        $emergencyBalance = (float) Account::where('is_emergency_fund', true)->sum('balance');

        $avgMonthlyExpense = $this->averageMonthlyExpense($reference);

        return $this->buildResult(
            income: $income,
            expense: $expense,
            monthlyInstallments: $monthlyInstallments,
            emergencyBalance: $emergencyBalance,
            avgMonthlyExpense: $avgMonthlyExpense,
        );
    }

    public function buildResult(
        float $income,
        float $expense,
        float $monthlyInstallments,
        float $emergencyBalance,
        float $avgMonthlyExpense,
    ): array {
        $net = max($income - $expense, 0);
        $savingsRate = $income > 0 ? round($net / $income * 100, 1) : 0.0;
        $dti = $income > 0 ? round($monthlyInstallments / $income * 100, 1) : ($monthlyInstallments > 0 ? 100.0 : 0.0);
        $emergencyMonths = $avgMonthlyExpense > 0 ? round($emergencyBalance / $avgMonthlyExpense, 1) : ($emergencyBalance > 0 ? 12.0 : 0.0);

        $savings = $this->savingsRateStatus($savingsRate);
        $debt = $this->dtiStatus($dti);
        $emergency = $this->emergencyStatus($emergencyMonths);

        $subscores = [
            $this->savingsRateSubscore($savingsRate),
            $this->dtiSubscore($dti),
            $this->emergencySubscore($emergencyMonths),
        ];
        $score = (int) round(array_sum($subscores) / count($subscores));

        return [
            'score' => $score,
            'score_label' => $this->scoreLabel($score),
            'ratios' => [
                'savings_rate' => [
                    'value' => $savingsRate,
                    'ideal' => self::IDEAL_SAVINGS_RATE,
                    'status' => $savings,
                    'status_label' => $this->statusLabel($savings),
                    'recommendation' => $this->savingsRecommendation($savingsRate),
                ],
                'dti' => [
                    'value' => $dti,
                    'ideal' => self::IDEAL_DTI,
                    'status' => $debt,
                    'status_label' => $this->statusLabel($debt),
                    'recommendation' => $this->dtiRecommendation($dti),
                ],
                'emergency_fund' => [
                    'value' => $emergencyMonths,
                    'min' => self::EMERGENCY_MIN_MONTHS,
                    'max' => self::EMERGENCY_MAX_MONTHS,
                    'status' => $emergency,
                    'status_label' => $this->statusLabel($emergency),
                    'recommendation' => $this->emergencyRecommendation($emergencyMonths),
                ],
            ],
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'monthly_installments' => $monthlyInstallments,
                'emergency_balance' => $emergencyBalance,
                'avg_monthly_expense' => $avgMonthlyExpense,
                'emergency_months' => $emergencyMonths,
            ],
        ];
    }

    public function savingsRateStatus(float $rate): string
    {
        if ($rate >= self::IDEAL_SAVINGS_RATE) {
            return 'healthy';
        }

        return $rate >= 10 ? 'warning' : 'danger';
    }

    public function dtiStatus(float $dti): string
    {
        if ($dti <= self::IDEAL_DTI) {
            return 'healthy';
        }

        return $dti <= 43 ? 'warning' : 'danger';
    }

    public function emergencyStatus(float $months): string
    {
        if ($months >= self::EMERGENCY_MIN_MONTHS && $months <= self::EMERGENCY_MAX_MONTHS) {
            return 'healthy';
        }

        return $months > 0 ? 'warning' : 'danger';
    }

    public function savingsRateSubscore(float $rate): float
    {
        return (float) min($rate / self::IDEAL_SAVINGS_RATE * 100, 100);
    }

    public function dtiSubscore(float $dti): float
    {
        if ($dti <= self::IDEAL_DTI) {
            return 100;
        }

        return (float) max(100 - ($dti - self::IDEAL_DTI) * (100 / 70), 0);
    }

    public function emergencySubscore(float $months): float
    {
        if ($months >= self::EMERGENCY_MIN_MONTHS && $months <= self::EMERGENCY_MAX_MONTHS) {
            return 100;
        }

        if ($months < self::EMERGENCY_MIN_MONTHS) {
            return (float) max($months / self::EMERGENCY_MIN_MONTHS * 100, 0);
        }

        return (float) max(100 - ($months - self::EMERGENCY_MAX_MONTHS) * 25, 0);
    }

    public function scoreLabel(int $score): string
    {
        if ($score >= 80) {
            return 'Sehat';
        }

        if ($score >= 60) {
            return 'Cukup Baik';
        }

        if ($score >= 40) {
            return 'Perlu Perhatian';
        }

        return 'Kritis';
    }

    public function statusLabel(string $status): string
    {
        return [
            'healthy' => 'Sehat',
            'warning' => 'Waspada',
            'danger' => 'Perlu Perhatian',
        ][$status] ?? $status;
    }

    public function savingsRecommendation(float $rate): string
    {
        if ($rate >= self::IDEAL_SAVINGS_RATE) {
            return "Tabungan Anda {$rate}% dari pemasukan — di atas ideal 20%. Pertahankan kebiasaan menabung ini.";
        }

        if ($rate >= 10) {
            return "Tabungan Anda {$rate}% dari pemasukan, di bawah ideal 20%. Coba sisihkan sedikit lebih banyak tiap bulan.";
        }

        return "Tabungan Anda {$rate}% dari pemasukan, tergolong rendah. Mulai sisihkan minimal 20% pemasukan untuk cadangan.";
    }

    public function dtiRecommendation(float $dti): string
    {
        if ($dti <= self::IDEAL_DTI) {
            return "Rasio utang Anda {$dti}% — di bawah batas ideal 30%. Beban cicilan masih aman.";
        }

        if ($dti <= 43) {
            return "Rasio utang Anda {$dti}%, di atas ideal 30%. Pertimbangkan kurangi pembukaan cicilan baru.";
        }

        return "Rasio utang Anda {$dti}%, tergolong tinggi. Sebaiknya fokus melunasi utang sebelum menambah cicilan.";
    }

    public function emergencyRecommendation(float $months): string
    {
        if ($months >= self::EMERGENCY_MIN_MONTHS && $months <= self::EMERGENCY_MAX_MONTHS) {
            return "Dana darurat Anda cukup untuk {$months} bulan pengeluaran — sudah di kisaran ideal 3–6 bulan.";
        }

        if ($months > 0) {
            return "Dana darurat Anda hanya menutupi {$months} bulan pengeluaran. Targetkan 3–6 bulan agar lebih aman.";
        }

        return 'Anda belum memiliki dana darurat. Sisihkan saldo ke akun yang ditandai sebagai Dana Darurat.';
    }

    private function averageMonthlyExpense($reference): float
    {
        $months = [];
        for ($i = 2; $i >= 0; $i--) {
            $month = $reference->copy()->startOfMonth()->subMonths($i);
            $months[] = (float) Transaction::where('type', 'expense')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('amount');
        }

        $months = array_filter($months, fn ($v) => $v > 0);

        return count($months) > 0 ? array_sum($months) / count($months) : 0.0;
    }
}
