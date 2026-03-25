<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\NoteAdded;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

class AddNote
{
    public function execute(
        string $question_id,
        string $body,
        ?CarbonImmutable $occurred_at = null,
    ): NoteAdded {
        $event = verb(new NoteAdded(
            question_id: $question_id,
            body: $body,
            occurred_at: $occurred_at,
        ));

        Verbs::commit();

        return $event;
    }
}
