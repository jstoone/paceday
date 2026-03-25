<?php

use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundEnded;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\Events\RoundVoided;
use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

it('voids an active round and updates state', function () {
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

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);

    expect($roundState->status)->toBe('voided')
        ->and($roundState->voided_at)->not->toBeNull();
});

it('voids an ended round and updates state', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);

    expect($roundState->status)->toBe('voided');
});

it('prevents voiding an already voided round', function () {
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

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
})->throws(Exception::class, 'Cannot void a round that is already voided.');

it('clears active_round_id when voiding an active round', function () {
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

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $questionState = QuestionState::load($questionEvent->question_id);
    expect($questionState->active_round_id)->toBeNull();
});

it('projects voided round to eloquent models', function () {
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

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $round = Round::find($roundEvent->round_id);

    expect($round->status)->toBe('voided')
        ->and($round->voided_at)->not->toBeNull();

    $question = Question::find($questionEvent->question_id);
    expect($question->active_round_id)->toBeNull();
});

it('creates a timeline entry when a round is voided', function () {
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

    verb(new RoundVoided(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $voidEntry = TimelineEntry::where('question_id', $questionEvent->question_id)
        ->where('type', 'round_voided')
        ->first();

    expect($voidEntry)->not->toBeNull()
        ->and($voidEntry->metadata['round_id'])->toBe($roundEvent->round_id);
});
