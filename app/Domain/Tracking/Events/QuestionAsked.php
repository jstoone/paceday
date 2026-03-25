<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class QuestionAsked extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public ?string $question_id = null,
        public int $user_id = 0,
        public string $label = '',
        public string $thing = '',
        public string $unit = '',
        public int $amount = 0,
        public string $question_type = 'how_long',
    ) {}

    public function apply(QuestionState $state): void
    {
        $state->user_id = $this->user_id;
        $state->label = $this->label;
        $state->thing = $this->thing;
        $state->unit = $this->unit;
        $state->amount = $this->amount;
        $state->question_type = $this->question_type;
    }

    #[Once]
    public function handle(): void
    {
        Question::create([
            'id' => $this->question_id,
            'user_id' => $this->user_id,
            'label' => $this->label,
            'thing' => $this->thing,
            'unit' => $this->unit,
            'amount' => $this->amount,
            'question_type' => $this->question_type,
        ]);
    }
}
