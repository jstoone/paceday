<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class GuessUpdated extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public string $guess,
        public ?CarbonImmutable $occurred_at = null,
        public ?CarbonImmutable $recorded_at = null,
    ) {
        $this->occurred_at ??= now()->toImmutable();
        $this->recorded_at ??= now()->toImmutable();
    }

    public function apply(QuestionState $state): void
    {
        $state->guess = $this->guess;
    }

    #[Once]
    public function handle(): void
    {
        Question::where('id', $this->question_id)
            ->update(['guess' => $this->guess]);

        TimelineEntry::create([
            'question_id' => $this->question_id,
            'type' => 'guess_updated',
            'body' => $this->guess,
            'occurred_at' => $this->occurred_at,
            'recorded_at' => $this->recorded_at,
        ]);
    }
}
