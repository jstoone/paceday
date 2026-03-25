<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundStarted;

class AskQuestion
{
    public function execute(
        int $user_id,
        string $thing,
        string $unit,
        int $amount,
        ?string $guess = null,
        ?string $note = null,
    ): QuestionAsked {
        $event = verb(new QuestionAsked(
            user_id: $user_id,
            label: "How long does {$amount} {$unit} of {$thing} last?",
            thing: $thing,
            unit: $unit,
            amount: $amount,
        ));

        verb(new RoundStarted(
            question_id: $event->question_id,
            guess: $guess,
            note: $note,
        ));

        return $event;
    }
}
