<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\RoundVoided;
use App\Domain\Tracking\States\RoundState;
use Thunk\Verbs\Facades\Verbs;

class VoidRound
{
    public function execute(
        string $round_id,
        ?string $note = null,
    ): RoundVoided {
        $round = RoundState::load($round_id);

        $event = verb(new RoundVoided(
            round_id: $round_id,
            question_id: $round->question_id,
        ));

        if ($note !== null) {
            verb(new NoteAdded(
                question_id: $round->question_id,
                body: $note,
            ));
        }

        Verbs::commit();

        return $event;
    }
}
