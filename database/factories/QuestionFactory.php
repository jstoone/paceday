<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(1, 100);
        $unit = fake()->randomElement(['capsules', 'rolls', 'bottles', 'packs', 'bags']);
        $thing = fake()->randomElement(['coffee', 'toilet paper', 'dish soap', 'trash bags', 'toothpaste']);

        return [
            'user_id' => User::factory(),
            'label' => "How long does {$amount} {$unit} of {$thing} last?",
            'thing' => $thing,
            'unit' => $unit,
            'amount' => $amount,
            'question_type' => 'duration',
        ];
    }

    public function frequency(): static
    {
        return $this->state(fn () => [
            'label' => 'How many times do I '.fake()->randomElement(['exercise', 'meditate', 'cook', 'read']).' per '.fake()->randomElement(['day', 'week', 'month']).'?',
            'thing' => fake()->randomElement(['exercise', 'meditate', 'cook', 'read']),
            'unit' => null,
            'amount' => null,
            'question_type' => 'frequency',
            'period' => fake()->randomElement(['daily', 'weekly', 'monthly']),
        ]);
    }
}
