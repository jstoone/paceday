<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Event;

class UsageLogged extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public ?CarbonImmutable $occurred_at = null,
        public ?CarbonImmutable $recorded_at = null,
    ) {
        $this->occurred_at ??= now()->toImmutable();
        $this->recorded_at ??= now()->toImmutable();
    }

    public function handle(): void
    {
        TimelineEntry::updateOrCreate(
            ['event_id' => $this->id],
            [
                'question_id' => $this->question_id,
                'type' => 'usage_logged',
                'occurred_at' => $this->occurred_at,
                'recorded_at' => $this->recorded_at,
            ],
        );
    }
}
