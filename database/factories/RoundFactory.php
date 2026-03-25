<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Round>
 */
class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'status' => 'active',
            'occurred_at' => now(),
        ];
    }
}
