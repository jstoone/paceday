<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\QuestionType;
use App\Domain\Tracking\States\QuestionState;
use Carbon\CarbonImmutable;

class RecordEntry
{
    /**
     * @return array{action: string, redirect?: string}
     */
    public function execute(
        string $question_id,
        ?CarbonImmutable $occurred_at = null,
        ?string $note = null,
    ): array {
        $state = QuestionState::load($question_id);

        if ($state->question_type === QuestionType::Frequency) {
            app(LogUsage::class)->execute(
                question_id: $question_id,
                occurred_at: $occurred_at,
                note: $note,
            );

            return ['action' => 'usage_logged'];
        }

        if ($state->active_round_id !== null) {
            app(EndRound::class)->execute(
                round_id: $state->active_round_id,
                occurred_at: $occurred_at,
                note: $note,
            );

            return ['action' => 'round_ended'];
        }

        return ['action' => 'start_round', 'redirect' => 'start-round'];
    }
}
