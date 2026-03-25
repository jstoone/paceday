<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\GuessUpdated;
use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\RoundStarted;
use Thunk\Verbs\Facades\Verbs;

class StartRound
{
    public function execute(
        string $question_id,
        ?string $guess = null,
        ?string $note = null,
    ): RoundStarted {
        $event = verb(new RoundStarted(
            question_id: $question_id,
        ));

        if ($guess !== null) {
            verb(new GuessUpdated(
                question_id: $question_id,
                guess: $guess,
            ));
        }

        if ($note !== null) {
            verb(new NoteAdded(
                question_id: $question_id,
                body: $note,
            ));
        }

        Verbs::commit();

        return $event;
    }
}
