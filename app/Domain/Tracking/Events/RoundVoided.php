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

class RoundVoided extends Event
{
    public function __construct(
        #[StateUlid(RoundState::class)]
        public string $round_id,
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public ?CarbonImmutable $voided_at = null,
    ) {
        $this->voided_at ??= now()->toImmutable();
    }

    public function validateRound(RoundState $round): void
    {
        $this->assert(
            in_array($round->status, ['active', 'ended']),
            'Cannot void a round that is already voided.',
        );
    }

    public function applyToRound(RoundState $round): void
    {
        $round->status = 'voided';
        $round->voided_at = $this->voided_at;
    }

    public function applyToQuestion(QuestionState $question): void
    {
        if ($question->active_round_id === $this->round_id) {
            $question->active_round_id = null;
        }
    }

    public function handle(): void
    {
        Round::where('id', $this->round_id)->update([
            'status' => 'voided',
            'voided_at' => $this->voided_at,
        ]);

        Question::where('active_round_id', $this->round_id)
            ->update(['active_round_id' => null]);

        TimelineEntry::updateOrCreate(
            ['event_id' => $this->id],
            [
                'question_id' => $this->question_id,
                'type' => 'round_voided',
                'occurred_at' => $this->voided_at,
                'recorded_at' => $this->voided_at,
                'metadata' => ['round_id' => $this->round_id],
            ],
        );
    }
}
