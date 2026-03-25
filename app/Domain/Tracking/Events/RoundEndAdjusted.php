<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\RoundState;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class RoundEndAdjusted extends Event
{
    public string $question_id;

    public function __construct(
        #[StateUlid(RoundState::class)]
        public string $round_id,
        public CarbonImmutable $old_ended_at,
        public CarbonImmutable $new_ended_at,
    ) {}

    public function validateRound(RoundState $round): void
    {
        $this->assert(
            $round->ended_at !== null,
            'Cannot adjust end date of a round that has not ended.',
        );
    }

    public function applyToRound(RoundState $round): void
    {
        $this->question_id = $round->question_id;
        $round->ended_at = $this->new_ended_at;
    }

    #[Once]
    public function handle(): void
    {
        Round::where('id', $this->round_id)->update([
            'ended_at' => $this->new_ended_at,
        ]);

        // Update the round_ended timeline entry to reflect the new date
        TimelineEntry::where('type', 'round_ended')
            ->whereJsonContains('metadata->round_id', $this->round_id)
            ->update(['occurred_at' => $this->new_ended_at]);
    }
}
