<?php

use App\Domain\Tracking\Actions\RetireQuestion;
use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\QuestionRetired;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\Question;
use App\Models\TimelineEntry;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

it('retires a question and updates state', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
    ));
    Verbs::commit();

    verb(new QuestionRetired(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $state = QuestionState::load($questionEvent->question_id);

    expect($state->retired_at)->not->toBeNull();
});

it('projects retired_at to the Question model', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
    ));
    Verbs::commit();

    verb(new QuestionRetired(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $question = Question::find($questionEvent->question_id);

    expect($question->retired_at)->not->toBeNull();
});

it('creates a timeline entry when a question is retired', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
    ));
    Verbs::commit();

    verb(new QuestionRetired(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $entry = TimelineEntry::where('question_id', $questionEvent->question_id)
        ->where('type', 'question_retired')
        ->first();

    expect($entry)->not->toBeNull();
});

it('prevents retiring an already retired question', function () {
    $user = User::factory()->create();

    $questionEvent = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
    ));
    Verbs::commit();

    verb(new QuestionRetired(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    verb(new QuestionRetired(
        question_id: $questionEvent->question_id,
    ));
})->throws(Exception::class, 'Question is already retired.');

describe('RetireQuestion action', function () {
    it('retires a question without an active round', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        (new RetireQuestion)->execute(
            question_id: $questionEvent->question_id,
        );

        $question = Question::find($questionEvent->question_id);

        expect($question->retired_at)->not->toBeNull();
    });

    it('voids the active round when retiring', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));

        $roundEvent = verb(new RoundStarted(
            question_id: $questionEvent->question_id,
        ));
        Verbs::commit();

        (new RetireQuestion)->execute(
            question_id: $questionEvent->question_id,
        );

        $roundState = RoundState::load($roundEvent->round_id);
        $question = Question::find($questionEvent->question_id);

        expect($roundState->status)->toBe('voided')
            ->and($question->retired_at)->not->toBeNull()
            ->and($question->active_round_id)->toBeNull();
    });

    it('stores an optional note when retiring', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        (new RetireQuestion)->execute(
            question_id: $questionEvent->question_id,
            note: 'Switching to a different brand',
        );

        $noteEntry = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'note')
            ->first();

        expect($noteEntry)->not->toBeNull()
            ->and($noteEntry->body)->toBe('Switching to a different brand');
    });
});
