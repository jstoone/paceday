<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\QuestionState;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Event;

class QuestionAsked extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public ?string $question_id = null,
    ) {}
}
