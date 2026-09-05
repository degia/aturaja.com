<?php

namespace Database\Seeders;

use App\Domain\Categories\Actions\SeedDefaultCategories;
use App\Domain\NetWorth\Actions\NetWorthCalculator;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Liability;
use App\Models\NetWorthSnapshot;
use App\Models\RecurringRule;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NuvistaDemoSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@nuvista.studio';

    private const ADMIN_PASSWORD = 'password123';

    private const ADMIN_NAME = 'Admin Nuvista';

    private const WORKSPACE_NAME = 'Nuvista Studio';

    private const LOOKBACK_MONTHS = 6;

    public function run(): void
    {
        $user = User::firstOrNew(['email' => self::ADMIN_EMAIL]);

        if (! $user->exists) {
            $user->name = self::ADMIN_NAME;
            $user->password = self::ADMIN_PASSWORD;
            $user->email_verified_at = now();
            $user->save();
        } else {
            $user->password = self::ADMIN_PASSWORD;
            $user->saveQuietly();
        }

        $workspace = Workspace::query()
            ->where('owner_user_id', $user->id)
            ->where('name', self::WORKSPACE_NAME)
            ->first();

        if (! $workspace) {
            $workspace = Workspace::create([
                'name' => self::WORKSPACE_NAME,
                'owner_user_id' => $user->id,
                'currency' => 'IDR',
            ]);
        }

        if (! $user->workspaces()->whereKey($workspace->id)->exists()) {
            $user->workspaces()->attach($workspace->id, ['role' => 'owner']);
        }

        if ($workspace->accounts()->count() > 0) {
            return;
        }

        $previousWorkspace = session('current_workspace_id');
        session(['current_workspace_id' => $workspace->id]);

        try {
            if ($workspace->categories()->count() === 0) {
                SeedDefaultCategories::run($workspace);
            }

            $this->seedWorkspaceData($workspace);
        } finally {
            if ($previousWorkspace) {
                session(['current_workspace_id' => $previousWorkspace]);
            } else {
                session()->forget('current_workspace_id');
            }
        }
    }

    private function seedWorkspaceData(Workspace $workspace): void
    {
        $accounts = [
            'cash' => Account::create(['name' => 'Dompet', 'type' => 'cash', 'balance' => 0, 'icon' => '👛', 'color' => '#16A34A']),
            'bank' => Account::create(['name' => 'Bank BCA', 'type' => 'bank', 'balance' => 0, 'icon' => '🏦', 'color' => '#0EA5E9']),
            'ewallet' => Account::create(['name' => 'E-Wallet GoPay', 'type' => 'ewallet', 'balance' => 0, 'icon' => '📱', 'color' => '#8B5CF6']),
            'emergency' => Account::create(['name' => 'Tabungan Darurat', 'type' => 'saving', 'balance' => 0, 'is_emergency_fund' => true, 'icon' => '🛡️', 'color' => '#059669']),
            'cc' => Account::create(['name' => 'Kartu Kredit BCA', 'type' => 'credit_card', 'balance' => 0, 'credit_limit' => 10_000_000, 'billing_date' => 5, 'due_date' => 23, 'icon' => '💳', 'color' => '#EF4444']),
        ];

        $tags = [
            'penting' => Tag::create(['name' => 'penting', 'color' => '#DC2626']),
            'rutin' => Tag::create(['name' => 'rutin', 'color' => '#EAB308']),
            'proyek' => Tag::create(['name' => 'proyek', 'color' => '#0EA5E9']),
        ];

        $category = fn (string $name) => Category::query()->where('workspace_id', $workspace->id)->where('name', $name)->first();

        $categories = [
            'gaji' => $category('Gaji'),
            'bonus' => $category('Bonus'),
            'bisnis' => $category('Pendapatan Bisnis'),
            'makan' => $category('Makanan'),
            'transport' => $category('Transportasi'),
            'utilitas' => $category('Tagihan & Utilitas'),
            'belanja' => $category('Belanja'),
            'kesehatan' => $category('Kesehatan'),
            'pendidikan' => $category('Pendidikan'),
            'hiburan' => $category('Hiburan'),
            'lifestyle' => $category('Gaya Hidup'),
            'rumah' => $category('Rumah'),
            'lain' => $category('Lainnya (Keluar)'),
        ];

        $now = now()->startOfDay();

        for ($i = self::LOOKBACK_MONTHS - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->startOfMonth()->subMonths($i);
            $isCurrent = $i === 0;
            $lastDay = $isCurrent ? (int) $now->format('j') : (int) $monthStart->copy()->endOfMonth()->format('j');

            $this->seedMonth($accounts, $categories, $tags, $monthStart, $lastDay);
        }

        $this->seedBudgets($tags, $categories, $now);
        $this->seedRecurringRules($accounts, $categories, $now);
        $this->seedAssets();
        $this->seedLiabilities();
        $this->seedDebts($accounts, $now);
        $this->seedNetWorthSnapshots($now);
    }

    private function seedMonth(array $accounts, array $c, array $t, Carbon $monthStart, int $lastDay): void
    {
        $m = (int) $monthStart->format('n');

        $tx = function (int $day, string $from, string $type, float $amount, ?string $cat, string $note, ?string $to = null, array $tags = []) use ($accounts, $c, $t, $monthStart, $lastDay): void {
            if ($day < 1 || $day > $lastDay) {
                return;
            }

            $transaction = Transaction::create([
                'account_id' => $accounts[$from]->id,
                'category_id' => $cat !== null && isset($c[$cat]) ? ($c[$cat]?->id ?? null) : null,
                'transfer_to_account_id' => $to !== null ? $accounts[$to]->id : null,
                'type' => $type,
                'amount' => $amount,
                'transaction_date' => $monthStart->copy()->addDays($day - 1)->format('Y-m-d'),
                'note' => $note,
            ]);

            if ($tags) {
                $ids = array_map(fn (string $key) => $t[$key]->id, $tags);
                $transaction->tags()->attach($ids);
            }
        };

        $tx(1, 'bank', 'income', 10_000_000, 'gaji', 'Gaji bulanan', tags: ['rutin']);
        $tx(1, 'bank', 'transfer', 2_800_000, null, 'Top up e-wallet', 'ewallet');
        $tx(5, 'bank', 'transfer', 1_800_000, null, 'Tabungan dana darurat', 'emergency', ['penting']);
        $tx(6, 'bank', 'transfer', 600_000, null, 'Tarik tunai', 'cash');
        $tx(22, 'bank', 'transfer', 700_000, null, 'Bayar tagihan kartu kredit', 'cc', ['penting']);

        if ($m % 4 === 1) {
            $tx(12, 'bank', 'income', 3_200_000, 'bisnis', 'Pembayaran proyek web klien', tags: ['proyek']);
        }

        if ($m % 3 === 0) {
            $tx(20, 'bank', 'income', 1_500_000, 'bonus', 'Bonus penyelesaian proyek', tags: ['proyek']);
        }

        $tx(13, 'cash', 'expense', 60_000, 'makan', 'Sarapan di warteg');
        $tx(19, 'cash', 'expense', 20_000, 'transport', 'Parkir kendaraan');
        $tx(28, 'cash', 'expense', 75_000, 'makan', 'Makan malam keluarga');

        $tx(3, 'bank', 'expense', 2_500_000, 'rumah', 'Sewa rumah', tags: ['rutin']);
        $tx(4, 'bank', 'expense', 950_000, 'utilitas', 'Listrik & internet', tags: ['rutin']);
        $tx(4, 'bank', 'expense', 150_000, 'utilitas', 'Pulsa & paket data', tags: ['rutin']);
        $tx(10, 'bank', 'expense', 90_000, 'kesehatan', 'Vitamin & obat-obatan');

        if ($m % 2 === 0) {
            $tx(15, 'bank', 'expense', 350_000, 'pendidikan', 'Kursus / kelas online');
        }

        $foods = [2 => 120_000, 5 => 95_000, 8 => 150_000, 11 => 80_000, 14 => 110_000, 17 => 75_000, 21 => 130_000, 26 => 100_000];
        foreach ($foods as $day => $amount) {
            $tx($day, 'ewallet', 'expense', $amount, 'makan', 'Makanan harian');
        }

        $transports = [3 => 70_000, 9 => 120_000, 16 => 80_000, 23 => 65_000];
        foreach ($transports as $day => $amount) {
            $tx($day, 'ewallet', 'expense', $amount, 'transport', 'Transportasi harian');
        }

        $tx(12, 'ewallet', 'expense', 150_000, 'hiburan', 'Nonton bioskop');
        $tx(24, 'ewallet', 'expense', 120_000, 'hiburan', 'Nongkrong bersama teman');
        $tx(8, 'ewallet', 'expense', 180_000, 'lifestyle', 'Perawatan diri');
        $tx(18, 'ewallet', 'expense', 160_000, 'lifestyle', 'Kopi & hangout');
        $tx(27, 'ewallet', 'expense', 90_000, 'lain', 'Top up aplikasi');
        $tx(22, 'ewallet', 'expense', 250_000, 'belanja', 'Belanja bulanan');

        $tx(7, 'cc', 'expense', 450_000, 'belanja', 'Belanja online via kartu');
        $tx(14, 'cc', 'expense', 320_000, 'belanja', 'Belanja kebutuhan off-air');
    }

    private function seedBudgets(array $tags, array $c, Carbon $now): void
    {
        $period = $now->copy()->startOfMonth();

        $limits = [
            'makan' => 1_250_000,
            'transport' => 500_000,
            'utilitas' => 1_100_000,
            'belanja' => 800_000,
            'kesehatan' => 300_000,
            'hiburan' => 300_000,
            'lifestyle' => 400_000,
            'rumah' => 2_500_000,
        ];

        foreach ($limits as $key => $limit) {
            $category = $c[$key];

            if (! $category) {
                continue;
            }

            Budget::firstOrCreate(
                [
                    'workspace_id' => $category->workspace_id,
                    'period_month' => $period->format('Y-m-d'),
                    'category_id' => $category->id,
                ],
                ['limit_amount' => $limit, 'alert_threshold_percent' => 80],
            );
        }
    }

    private function seedRecurringRules(array $accounts, array $c, Carbon $now): void
    {
        $monthStart = $now->copy()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();

        RecurringRule::create([
            'account_id' => $accounts['bank']->id,
            'category_id' => $c['gaji']->id,
            'type' => 'income',
            'amount' => 10_000_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => $monthStart->copy()->subMonths(self::LOOKBACK_MONTHS - 1)->format('Y-m-d'),
            'next_run_date' => $nextMonth->format('Y-m-d'),
            'note' => 'Gaji bulanan',
            'is_active' => true,
        ]);

        RecurringRule::create([
            'account_id' => $accounts['bank']->id,
            'category_id' => $c['rumah']->id,
            'type' => 'expense',
            'amount' => 2_500_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => $monthStart->copy()->subMonths(self::LOOKBACK_MONTHS - 1)->format('Y-m-d'),
            'next_run_date' => $nextMonth->copy()->setDay(3)->format('Y-m-d'),
            'note' => 'Sewa rumah bulanan',
            'is_active' => true,
        ]);

        RecurringRule::create([
            'account_id' => $accounts['bank']->id,
            'category_id' => $c['utilitas']->id,
            'type' => 'expense',
            'amount' => 1_100_000,
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => $monthStart->copy()->subMonths(self::LOOKBACK_MONTHS - 1)->format('Y-m-d'),
            'next_run_date' => $nextMonth->copy()->setDay(5)->format('Y-m-d'),
            'note' => 'Listrik, internet & pulsa',
            'is_active' => true,
        ]);
    }

    private function seedAssets(): void
    {
        $updated = now()->startOfDay()->subDays(1);

        Asset::create(['name' => 'Rumah Utama', 'category' => 'real_estate', 'current_value' => 500_000_000, 'last_updated_at' => $updated->format('Y-m-d')]);
        Asset::create(['name' => 'Mobil', 'category' => 'vehicle', 'current_value' => 150_000_000, 'last_updated_at' => $updated->format('Y-m-d')]);
        Asset::create(['name' => 'Emas Batangan', 'category' => 'investment_gold', 'current_value' => 25_000_000, 'last_updated_at' => $updated->format('Y-m-d')]);
        Asset::create(['name' => 'Portofolio Saham', 'category' => 'investment_stock', 'current_value' => 30_000_000, 'last_updated_at' => $updated->format('Y-m-d')]);
        Asset::create(['name' => 'Reksa Dana Pasar Uang', 'category' => 'investment_mutual_fund', 'current_value' => 40_000_000, 'last_updated_at' => $updated->format('Y-m-d')]);
    }

    private function seedLiabilities(): void
    {
        Liability::create([
            'name' => 'KPR Rumah',
            'category' => 'mortgage',
            'principal_remaining' => 300_000_000,
            'monthly_installment' => 1_800_000,
            'interest_rate' => 6.50,
        ]);

        Liability::create([
            'name' => 'Kredit Mobil',
            'category' => 'vehicle_loan',
            'principal_remaining' => 30_000_000,
            'monthly_installment' => 1_100_000,
            'interest_rate' => 4.90,
        ]);
    }

    private function seedDebts(array $accounts, Carbon $now): void
    {
        $monthStart = $now->copy()->startOfMonth();

        $payable = Debt::create([
            'direction' => 'payable',
            'counterparty_name' => 'Ayah',
            'principal_amount' => 8_000_000,
            'remaining_amount' => 8_000_000,
            'installments_count' => 8,
            'first_due_date' => $monthStart->copy()->subMonths(3)->addDays(14)->format('Y-m-d'),
            'default_account_id' => $accounts['bank']->id,
            'status' => 'ongoing',
            'note' => 'Pinjaman renovasi kamar (demo)',
        ]);

        $payable->generateSchedule();

        for ($i = 3; $i >= 1; $i--) {
            DebtPayment::create([
                'debt_id' => $payable->id,
                'account_id' => $accounts['bank']->id,
                'amount' => 1_000_000,
                'paid_at' => $monthStart->copy()->subMonths($i)->addDays(15)->format('Y-m-d'),
                'note' => 'Cicilan utang ke-'.(4 - $i),
            ]);
        }

        $receivable = Debt::create([
            'direction' => 'receivable',
            'counterparty_name' => 'Andi',
            'principal_amount' => 3_000_000,
            'remaining_amount' => 3_000_000,
            'due_date' => $monthStart->copy()->addMonth()->endOfMonth()->format('Y-m-d'),
            'status' => 'ongoing',
            'note' => 'Pinjaman teman (demo)',
        ]);

        DebtPayment::create([
            'debt_id' => $receivable->id,
            'account_id' => $accounts['bank']->id,
            'amount' => 1_000_000,
            'paid_at' => $monthStart->copy()->subMonth()->addDays(10)->format('Y-m-d'),
            'note' => 'Angsuran piutang dari Andi',
        ]);
    }

    private function seedNetWorthSnapshots(Carbon $now): void
    {
        $totals = (new NetWorthCalculator())->calculate();

        $monthStart = $now->copy()->startOfMonth();
        $deltaAssets = 12_000_000;
        $deltaLiabilities = 2_000_000;

        for ($i = 0; $i < self::LOOKBACK_MONTHS; $i++) {
            $monthsBack = self::LOOKBACK_MONTHS - 1 - $i;

            $assets = round((float) $totals['assets'] - $deltaAssets * $monthsBack, 2);
            $liabilities = round((float) $totals['liabilities'] - $deltaLiabilities * $monthsBack, 2);

            NetWorthSnapshot::create([
                'snapshot_date' => $monthStart->copy()->subMonths($monthsBack)->format('Y-m-d'),
                'total_assets' => $assets,
                'total_liabilities' => $liabilities,
                'net_worth' => round($assets - $liabilities, 2),
            ]);
        }
    }
}