<?php

use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

it('creates a round state with active status', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        thing: 'coffee',
        unit: 'capsules',
        amount: 40,
    ));

    $roundEvent = verb(new RoundStarted(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);

    expect($roundState->status)->toBe('active')
        ->and($roundState->question_id)->toBe($questionEvent->question_id)
        ->and($roundState->occurred_at)->not->toBeNull();
});

it('links round to question via active_round_id', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        thing: 'coffee',
        unit: 'capsules',
        amount: 40,
    ));

    $roundEvent = verb(new RoundStarted(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $questionState = QuestionState::load($questionEvent->question_id);

    expect($questionState->active_round_id)->toBe($roundEvent->round_id);
});
