<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Models\TimelineEntry;
use App\Support\Verbs\StateUlid;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class QuestionRetired extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,
        public ?CarbonImmutable $retired_at = null,
    ) {
        $this->retired_at ??= now()->toImmutable();
    }

    public function validate(QuestionState $state): void
    {
        $this->assert(
            $state->retired_at === null,
            'Question is already retired.',
        );
    }

    public function apply(QuestionState $state): void
    {
        $state->retired_at = $this->retired_at;
    }

    #[Once]
    public function handle(): void
    {
        Question::where('id', $this->question_id)->update([
            'retired_at' => $this->retired_at,
        ]);

        TimelineEntry::create([
            'question_id' => $this->question_id,
            'type' => 'question_retired',
            'occurred_at' => $this->retired_at,
            'recorded_at' => $this->retired_at,
        ]);
    }
}
