<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\QuestionRetired;
use App\Domain\Tracking\Events\RoundVoided;
use App\Domain\Tracking\States\QuestionState;
use Thunk\Verbs\Facades\Verbs;

class RetireQuestion
{
    public function execute(
        string $question_id,
        ?string $note = null,
    ): QuestionRetired {
        $question = QuestionState::load($question_id);

        if ($question->active_round_id !== null) {
            verb(new RoundVoided(
                round_id: $question->active_round_id,
                question_id: $question_id,
            ));
        }

        $event = verb(new QuestionRetired(
            question_id: $question_id,
        ));

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
