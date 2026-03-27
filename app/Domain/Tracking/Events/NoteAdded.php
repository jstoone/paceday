<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Event;

class NoteAdded extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public string $body,
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
                'type' => 'note',
                'body' => $this->body,
                'occurred_at' => $this->occurred_at,
                'recorded_at' => $this->recorded_at,
            ],
        );
    }
}
