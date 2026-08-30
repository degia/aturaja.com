<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['cash', 'bank', 'ewallet', 'credit_card']),
            'balance' => 0,
        ];
    }

    public function creditCard(): static
    {
        return $this->state(fn () => [
            'type' => 'credit_card',
            'credit_limit' => 5_000_000,
        ]);
    }
}