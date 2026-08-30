<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 1_000, 500_000),
            'transaction_date' => fake()->date(),
            'note' => fake()->sentence(),
            'is_reconciled' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => 'income',
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => 'expense',
            'category_id' => Category::factory()->expense(),
        ]);
    }

    public function transfer(Account $from, Account $to): static
    {
        return $this->state(fn () => [
            'type' => 'transfer',
            'account_id' => $from->id,
            'transfer_to_account_id' => $to->id,
            'category_id' => null,
        ]);
    }
}