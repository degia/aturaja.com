<?php

namespace Database\Seeders;

use App\Domain\Categories\Actions\SeedDefaultCategories;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        session()->forget('current_workspace_id');

        foreach (Workspace::orderBy('id')->get() as $workspace) {
            session(['current_workspace_id' => $workspace->id]);

            if ($workspace->categories()->count() === 0) {
                SeedDefaultCategories::run($workspace);
            }

            if ($workspace->accounts()->count() === 0) {
                $this->createDemoData($workspace);
            }
        }

        session()->forget('current_workspace_id');
    }

    private function createDemoData(Workspace $workspace): void
    {
        $cash = Account::create([
            'name' => 'Dompet',
            'type' => 'cash',
            'balance' => 0,
            'icon' => '👛',
            'color' => '#16A34A',
        ]);

        $bank = Account::create([
            'name' => 'Bank Contoh',
            'type' => 'bank',
            'balance' => 0,
            'icon' => '🏦',
            'color' => '#0EA5E9',
        ]);

        $ewallet = Account::create([
            'name' => 'E-Wallet Contoh',
            'type' => 'ewallet',
            'balance' => 0,
            'icon' => '📱',
            'color' => '#8B5CF6',
        ]);

        $tag = Tag::create(['name' => 'penting', 'color' => '#DC2626']);
        $tag2 = Tag::create(['name' => 'rutin', 'color' => '#EAB308']);

        $gaji = Category::where('workspace_id', $workspace->id)->where('name', 'Gaji')->first();
        $makan = Category::where('workspace_id', $workspace->id)->where('name', 'Makanan')->first();
        $transport = Category::where('workspace_id', $workspace->id)->where('name', 'Transportasi')->first();
        $tagihan = Category::where('workspace_id', $workspace->id)->where('name', 'Tagihan & Utilitas')->first();

        $income = Transaction::create([
            'account_id' => $bank->id,
            'category_id' => $gaji?->id,
            'type' => 'income',
            'amount' => 7_500_000,
            'transaction_date' => now()->startOfMonth()->addDays(1),
            'note' => 'Gaji bulan ini (contoh)',
        ]);
        $income->tags()->attach($tag2);

        Transaction::create([
            'account_id' => $ewallet->id,
            'category_id' => $makan?->id,
            'type' => 'expense',
            'amount' => 125_000,
            'transaction_date' => now()->startOfMonth()->addDays(2),
            'note' => 'Makan siang (contoh)',
        ]);

        Transaction::create([
            'account_id' => $ewallet->id,
            'category_id' => $transport?->id,
            'type' => 'expense',
            'amount' => 40_000,
            'transaction_date' => now()->startOfMonth()->addDays(3),
            'note' => 'Transportasi (contoh)',
        ]);

        Transaction::create([
            'account_id' => $bank->id,
            'category_id' => $tagihan?->id,
            'type' => 'expense',
            'amount' => 1_200_000,
            'transaction_date' => now()->startOfMonth()->addDays(4),
            'note' => 'Tagihan listrik & internet (contoh)',
        ]);

        Transaction::create([
            'account_id' => $bank->id,
            'transfer_to_account_id' => $cash->id,
            'type' => 'transfer',
            'amount' => 1_000_000,
            'transaction_date' => now()->startOfMonth()->addDays(5),
            'note' => 'Transfer ke tunai (contoh)',
        ]);
    }
}