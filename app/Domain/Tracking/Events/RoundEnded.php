<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Event;

class RoundEnded extends Event
{
    public function __construct(
        #[StateUlid(RoundState::class)]
        public string $round_id,
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public ?CarbonImmutable $occurred_at = null,
        public ?CarbonImmutable $recorded_at = null,
    ) {
        $this->occurred_at ??= now()->toImmutable();
        $this->recorded_at ??= now()->toImmutable();
    }

    public function validateRound(RoundState $round): void
    {
        $this->assert(
            $round->status === 'active',
            'Cannot end a round that is not active.',
        );
    }

    public function applyToRound(RoundState $round): void
    {
        $round->status = 'ended';
        $round->ended_at = $this->occurred_at;
        $round->recorded_at = $this->recorded_at;
    }

    public function applyToQuestion(QuestionState $question): void
    {
        $question->active_round_id = null;
    }

    public function handle(): void
    {
        Round::where('id', $this->round_id)->update([
            'status' => 'ended',
            'ended_at' => $this->occurred_at,
            'recorded_at' => $this->recorded_at,
        ]);

        Question::where('active_round_id', $this->round_id)
            ->update(['active_round_id' => null]);

        TimelineEntry::updateOrCreate(
            ['event_id' => $this->id],
            [
                'question_id' => $this->question_id,
                'type' => 'round_ended',
                'occurred_at' => $this->occurred_at,
                'recorded_at' => $this->recorded_at,
                'metadata' => ['round_id' => $this->round_id],
            ],
        );
    }
}
