<?php

use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundEnded;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\States\QuestionState;
use App\Domain\Tracking\States\RoundState;
use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

it('creates a round state with active status', function () {
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
        question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
    ));

    $roundEvent = verb(new RoundStarted(
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $questionState = QuestionState::load($questionEvent->question_id);

    expect($questionState->active_round_id)->toBe($roundEvent->round_id);
});

it('ends an active round and updates state', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);

    expect($roundState->status)->toBe('ended')
        ->and($roundState->ended_at)->not->toBeNull()
        ->and($roundState->recorded_at)->not->toBeNull();
});

it('clears active_round_id on question when round ends', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $questionState = QuestionState::load($questionEvent->question_id);

    expect($questionState->active_round_id)->toBeNull();
});

it('projects round ending to eloquent models', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $round = Round::find($roundEvent->round_id);

    expect($round->status)->toBe('ended')
        ->and($round->ended_at)->not->toBeNull()
        ->and($round->recorded_at)->not->toBeNull();

    $question = Question::find($questionEvent->question_id);

    expect($question->active_round_id)->toBeNull();
});

it('prevents ending a round that is not active', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
})->throws(Exception::class, 'Cannot end a round that is not active.');

it('carries both recorded_at and occurred_at on RoundEnded', function () {
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

    $backdatedAt = now()->subDays(2)->toImmutable();

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
        occurred_at: $backdatedAt,
    ));
    Verbs::commit();

    $round = Round::find($roundEvent->round_id);

    expect($round->ended_at->format('Y-m-d'))->toBe($backdatedAt->format('Y-m-d'))
        ->and($round->recorded_at->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

it('projects round events to timeline entries', function () {
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

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
    ));
    Verbs::commit();

    $entries = TimelineEntry::where('question_id', $questionEvent->question_id)
        ->orderBy('occurred_at')
        ->get();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->type)->toBe('round_started')
        ->and($entries[1]->type)->toBe('round_ended');
});
