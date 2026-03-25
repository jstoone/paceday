<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\RoundEnded;
use App\Domain\Tracking\States\RoundState;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

class EndRound
{
    public function execute(
        string $round_id,
        ?CarbonImmutable $occurred_at = null,
        ?string $note = null,
    ): RoundEnded {
        $round = RoundState::load($round_id);

        $event = verb(new RoundEnded(
            round_id: $round_id,
            question_id: $round->question_id,
            occurred_at: $occurred_at,
        ));

        if ($note !== null) {
            verb(new NoteAdded(
                question_id: $round->question_id,
                body: $note,
                occurred_at: $occurred_at,
            ));
        }

        Verbs::commit();

        return $event;
    }
}
