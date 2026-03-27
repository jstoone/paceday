<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Data\FrequencyQuestion;
use App\Domain\Tracking\Events\GuessUpdated;
use App\Domain\Tracking\Events\NoteAdded;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\QuestionType;
use Thunk\Verbs\Facades\Verbs;

class AskQuestion
{
    public function execute(
        int $user_id,
        DurationQuestion|FrequencyQuestion $question,
        ?string $guess = null,
        ?string $note = null,
    ): QuestionAsked {
        $questionType = match (true) {
            $question instanceof DurationQuestion => QuestionType::Duration,
            $question instanceof FrequencyQuestion => QuestionType::Frequency,
        };

        $label = match (true) {
            $question instanceof DurationQuestion => "How long does {$question->amount} {$question->unit} of {$question->thing} last?",
            $question instanceof FrequencyQuestion => "How many times do I {$question->thing} per {$question->period->noun()}?",
        };

        $event = verb(new QuestionAsked(
            user_id: $user_id,
            label: $label,
            question_type: $questionType,
            question: $question,
        ));

        if ($questionType === QuestionType::Duration) {
            verb(new RoundStarted(
                question_id: $event->question_id,
            ));
        }

        if ($guess !== null) {
            verb(new GuessUpdated(
                question_id: $event->question_id,
                guess: $guess,
            ));
        }

        if ($note !== null) {
            verb(new NoteAdded(
                question_id: $event->question_id,
                body: $note,
            ));
        }

        Verbs::commit();

        return $event;
    }
}
