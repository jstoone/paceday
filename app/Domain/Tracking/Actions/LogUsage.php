<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\UsageLogged;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

class LogUsage
{
    public function execute(
        string $question_id,
        ?CarbonImmutable $occurred_at = null,
        ?string $note = null,
    ): UsageLogged {
        $event = verb(new UsageLogged(
            question_id: $question_id,
            occurred_at: $occurred_at,
        ));

        if ($note !== null) {
            verb(new NoteAdded(
                question_id: $question_id,
                body: $note,
                occurred_at: $occurred_at,
            ));
        }

        Verbs::commit();

        return $event;
    }
}
