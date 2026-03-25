<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\Question;
use App\Models\Round;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class RoundStarted extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        #[StateUlid(RoundState::class)]
        public ?string $round_id = null,
        public ?CarbonImmutable $occurred_at = null,
        public ?string $guess = null,
        public ?string $note = null,
    ) {
        $this->occurred_at ??= now()->toImmutable();
    }

    public function validateQuestion(QuestionState $question): void
    {
        $this->assert(
            $question->label !== '',
            'Cannot start a round on a question that does not exist.',
        );
    }

    public function applyToRound(RoundState $round): void
    {
        $round->question_id = $this->question_id;
        $round->status = 'active';
        $round->occurred_at = $this->occurred_at;
        $round->guess = $this->guess;
        $round->note = $this->note;
    }

    public function applyToQuestion(QuestionState $question): void
    {
        $question->active_round_id = $this->round_id;
    }

    #[Once]
    public function handle(): void
    {
        Round::create([
            'id' => $this->round_id,
            'question_id' => $this->question_id,
            'status' => 'active',
            'occurred_at' => $this->occurred_at,
            'guess' => $this->guess,
            'note' => $this->note,
        ]);

        Question::where('id', $this->question_id)
            ->update(['active_round_id' => $this->round_id]);
    }
}
