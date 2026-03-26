<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => strtolower(Str::random(4)),
            'question_id' => null,
        ];
    }

    public function linked(?Question $question = null): static
    {
        return $this->state(fn () => [
            'question_id' => $question?->id ?? Question::factory(),
        ]);
    }
}
