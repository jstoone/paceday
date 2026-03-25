<?php

use App\Domain\Tracking\Actions\AskQuestion;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

it('creates a question state with all fields', function () {
    $user = User::factory()->create();

    $event = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        thing: 'coffee',
        unit: 'capsules',
        amount: 40,
    ));
    Verbs::commit();

    $state = QuestionState::load($event->question_id);

    expect($state->user_id)->toBe($user->id)
        ->and($state->label)->toBe('How long does 40 capsules of coffee last?')
        ->and($state->thing)->toBe('coffee')
        ->and($state->unit)->toBe('capsules')
        ->and($state->amount)->toBe(40)
        ->and($state->question_type)->toBe('how_long');
});

it('projects a Question model after commit', function () {
    $user = User::factory()->create();

    $event = verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        thing: 'coffee',
        unit: 'capsules',
        amount: 40,
    ));
    Verbs::commit();

    $question = Question::find($event->question_id);

    expect($question)->not->toBeNull()
        ->and($question->label)->toBe('How long does 40 capsules of coffee last?')
        ->and($question->thing)->toBe('coffee')
        ->and($question->unit)->toBe('capsules')
        ->and($question->amount)->toBe(40)
        ->and($question->question_type)->toBe('how_long')
        ->and($question->user_id)->toBe($user->id);
});

it('does not create a round on its own', function () {
    $user = User::factory()->create();

    verb(new QuestionAsked(
        user_id: $user->id,
        label: 'How long does 40 capsules of coffee last?',
        thing: 'coffee',
        unit: 'capsules',
        amount: 40,
    ));
    Verbs::commit();

    expect(Round::count())->toBe(0);
});

describe('AskQuestion action', function () {
    it('creates a question and starts a round in one step', function () {
        $user = User::factory()->create();
        $action = new AskQuestion;

        $event = $action->execute(
            user_id: $user->id,
            thing: 'coffee',
            unit: 'capsules',
            amount: 40,
        );

        $question = Question::find($event->question_id);

        expect($question)->not->toBeNull()
            ->and($question->active_round_id)->not->toBeNull();

        $round = Round::find($question->active_round_id);

        expect($round)->not->toBeNull()
            ->and($round->status)->toBe('active')
            ->and($round->question_id)->toBe($event->question_id);
    });

    it('stores guess on question and note as timeline entry', function () {
        $user = User::factory()->create();
        $action = new AskQuestion;

        $event = $action->execute(
            user_id: $user->id,
            thing: 'coffee',
            unit: 'capsules',
            amount: 40,
            guess: '3 weeks',
            note: 'Starting with Nespresso pods',
        );

        $question = Question::find($event->question_id);
        $state = QuestionState::load($event->question_id);

        expect($question->guess)->toBe('3 weeks')
            ->and($state->guess)->toBe('3 weeks');

        $noteEntry = TimelineEntry::where('question_id', $event->question_id)
            ->where('type', 'note')
            ->first();

        expect($noteEntry)->not->toBeNull()
            ->and($noteEntry->body)->toBe('Starting with Nespresso pods');
    });

    it('works without guess and note', function () {
        $user = User::factory()->create();
        $action = new AskQuestion;

        $event = $action->execute(
            user_id: $user->id,
            thing: 'toilet paper',
            unit: 'rolls',
            amount: 8,
        );

        $question = Question::find($event->question_id);
        $state = QuestionState::load($event->question_id);

        expect($question->guess)->toBeNull()
            ->and($state->guess)->toBeNull();

        expect(TimelineEntry::where('question_id', $event->question_id)
            ->where('type', 'note')
            ->exists())->toBeFalse();
    });
});
