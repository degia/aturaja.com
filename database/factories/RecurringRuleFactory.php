<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringRule;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringRule>
 */
class RecurringRuleFactory extends Factory
{
    protected $model = RecurringRule::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory()->expense(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 10_000, 1_000_000),
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => fake()->date(),
            'next_run_date' => fake()->date(),
            'is_active' => true,
        ];
    }

    public function due(): static
    {
        return $this->state(fn () => ['next_run_date' => today()]);
    }
}