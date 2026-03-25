<?php

namespace Database\Seeders;

use App\Domain\Tracking\Actions\AskQuestion;
use App\Domain\Tracking\Actions\EndRound;
use App\Domain\Tracking\Actions\StartRound;
use App\Domain\Tracking\Actions\UpdateGuess;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::whereEmail('demo@paceday.test')->first()
            ?? User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo@paceday.test',
                'password' => 'demo@paceday.test',
            ]);

        $this->seedCoffeeQuestion($user);
        $this->seedToiletPaperQuestion($user);
    }

    private function seedCoffeeQuestion(User $user): void
    {
        $this->actingAs($user);

        // Round 1: Ask the question, which auto-starts the first round
        $this->travelTo('2025-12-01 09:00:00');

        $event = app(AskQuestion::class)->execute(
            user_id: $user->id,
            thing: 'coffee',
            unit: 'capsules',
            amount: 40,
            guess: '3 weeks',
            note: 'Starting to track my Nespresso pods',
        );

        $questionId = $event->question_id;
        $question = Question::findOrFail($questionId);

        // Round 1 ends after 18 days — faster than the 21-day guess
        $this->travelTo('2025-12-19 08:30:00');

        app(EndRound::class)->execute(
            round_id: $question->active_round_id,
            note: 'Went through these fast over the holidays',
        );

        // Round 2: Start a new round, revise guess down
        $this->travelTo('2025-12-20 10:00:00');

        $event = app(StartRound::class)->execute(
            question_id: $questionId,
            guess: '2 weeks',
            note: 'New box, darker roast this time',
        );

        // Round 2 ends after 23 days — slower than expected
        $this->travelTo('2026-01-12 19:00:00');

        app(EndRound::class)->execute(round_id: $event->round_id);

        // Round 3: No guess change
        $this->travelTo('2026-01-13 08:00:00');

        $event = app(StartRound::class)->execute(
            question_id: $questionId,
        );

        // Round 3 ends after 15 days
        $this->travelTo('2026-01-28 17:00:00');

        app(EndRound::class)->execute(
            round_id: $event->round_id,
            note: 'Had guests over, burned through these',
        );

        // Round 4: Update guess based on learning
        $this->travelTo('2026-01-29 09:00:00');

        app(UpdateGuess::class)->execute(
            question_id: $questionId,
            guess: '19 days',
        );

        $event = app(StartRound::class)->execute(
            question_id: $questionId,
        );

        // Round 4 ends after 20 days
        $this->travelTo('2026-02-18 11:00:00');

        app(EndRound::class)->execute(round_id: $event->round_id);

        // Round 5: Currently active
        $this->travelTo('2026-02-19 08:00:00');

        app(StartRound::class)->execute(
            question_id: $questionId,
        );

        // Return to real time
        Carbon::setTestNow();
    }

    private function seedToiletPaperQuestion(User $user): void
    {
        $this->actingAs($user);

        // Round 1
        $this->travelTo('2025-11-15 10:00:00');

        $event = app(AskQuestion::class)->execute(
            user_id: $user->id,
            thing: 'toilet paper',
            unit: 'rolls',
            amount: 12,
            guess: '6 weeks',
        );

        $questionId = $event->question_id;
        $question = Question::findOrFail($questionId);

        // Round 1 ends after 38 days
        $this->travelTo('2025-12-23 14:00:00');

        app(EndRound::class)->execute(round_id: $question->active_round_id);

        // Round 2
        $this->travelTo('2025-12-24 09:00:00');

        $event = app(StartRound::class)->execute(
            question_id: $questionId,
            guess: '5 weeks',
        );

        // Round 2 ends after 33 days
        $this->travelTo('2026-01-26 18:00:00');

        app(EndRound::class)->execute(round_id: $event->round_id);

        // Round 3: currently active
        $this->travelTo('2026-01-27 08:00:00');

        app(StartRound::class)->execute(
            question_id: $questionId,
        );

        Carbon::setTestNow();
    }

    private function travelTo(string $datetime): void
    {
        Carbon::setTestNow(CarbonImmutable::parse($datetime));
    }

    private function actingAs(User $user): void
    {
        auth()->login($user);
    }
}
