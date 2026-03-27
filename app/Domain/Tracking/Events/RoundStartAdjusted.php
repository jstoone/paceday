<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\RoundState;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Event;

class RoundStartAdjusted extends Event
{
    public string $question_id;

    public function __construct(
        #[StateUlid(RoundState::class)]
        public string $round_id,
        public CarbonImmutable $old_occurred_at,
        public CarbonImmutable $new_occurred_at,
    ) {}

    public function applyToRound(RoundState $round): void
    {
        $this->question_id = $round->question_id;
        $round->occurred_at = $this->new_occurred_at;
    }

    public function handle(): void
    {
        Round::where('id', $this->round_id)->update([
            'occurred_at' => $this->new_occurred_at,
        ]);

        // Update the round_started timeline entry to reflect the new date
        TimelineEntry::where('type', 'round_started')
            ->whereJsonContains('metadata->round_id', $this->round_id)
            ->update(['occurred_at' => $this->new_occurred_at]);
    }
}
