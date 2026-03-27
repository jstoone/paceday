<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Data\FrequencyQuestion;
use App\Domain\Tracking\QuestionType;
use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Event;

class QuestionAsked extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public ?string $question_id = null,
        public int $user_id = 0,
        public string $label = '',
        public QuestionType $question_type = QuestionType::Duration,
        public DurationQuestion|FrequencyQuestion|null $question = null,
    ) {}

    public function validateDuration(QuestionState $state): void
    {
        if ($this->question_type === QuestionType::Duration) {
            $this->assert(
                $this->question instanceof DurationQuestion,
                'Duration questions require a DurationQuestion data object.',
            );
        }
    }

    public function validateFrequency(QuestionState $state): void
    {
        if ($this->question_type === QuestionType::Frequency) {
            $this->assert(
                $this->question instanceof FrequencyQuestion,
                'Frequency questions require a FrequencyQuestion data object.',
            );
        }
    }

    public function apply(QuestionState $state): void
    {
        $state->user_id = $this->user_id;
        $state->label = $this->label;
        $state->question_type = $this->question_type;
        $state->question = $this->question;
    }

    public function handle(): void
    {
        Question::updateOrCreate(
            ['id' => $this->question_id],
            [
                'user_id' => $this->user_id,
                'label' => $this->label,
                'question_type' => $this->question_type,
                'thing' => $this->question->thing,
                'unit' => $this->question instanceof DurationQuestion ? $this->question->unit : null,
                'amount' => $this->question instanceof DurationQuestion ? $this->question->amount : null,
                'period' => $this->question instanceof FrequencyQuestion ? $this->question->period : null,
            ],
        );
    }
}
