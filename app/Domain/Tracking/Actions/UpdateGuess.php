<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\GuessUpdated;
use Thunk\Verbs\Facades\Verbs;

class UpdateGuess
{
    public function execute(
        string $question_id,
        string $guess,
    ): GuessUpdated {
        $event = verb(new GuessUpdated(
            question_id: $question_id,
            guess: $guess,
        ));

        Verbs::commit();

        return $event;
    }
}
