<?php

use App\Domain\Tracking\Actions\AskQuestion;
use App\Domain\Tracking\Actions\LogUsage;
use App\Domain\Tracking\Actions\RecordEntry;
use App\Domain\Tracking\Actions\UpdateGuess;
use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Data\FrequencyQuestion;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\UsageLogged;
use App\Domain\Tracking\Period;
use App\Domain\Tracking\QuestionType;
use App\Domain\Tracking\States\QuestionState;
use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

describe('QuestionAsked with frequency type', function () {
    it('creates a frequency question state with period', function () {
        $user = User::factory()->create();

        $event = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How many times do I exercise per week?',
            question_type: QuestionType::Frequency,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        ));
        Verbs::commit();

        $state = QuestionState::load($event->question_id);

        expect($state->question_type)->toBe(QuestionType::Frequency)
            ->and($state->question)->toBeInstanceOf(FrequencyQuestion::class)
            ->and($state->question->period)->toBe(Period::Weekly)
            ->and($state->question->thing)->toBe('exercise');
    });

    it('projects a frequency Question model with period', function () {
        $user = User::factory()->create();

        $event = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How many times do I exercise per week?',
            question_type: QuestionType::Frequency,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        ));
        Verbs::commit();

        $question = Question::find($event->question_id);

        expect($question->question_type)->toBe(QuestionType::Frequency)
            ->and($question->period)->toBe(Period::Weekly);
    });
});

describe('AskQuestion action with frequency', function () {
    it('creates a frequency question without auto-starting a round', function () {
        $user = User::factory()->create();

        $event = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        );

        $question = Question::find($event->question_id);

        expect($question)->not->toBeNull()
            ->and($question->question_type)->toBe(QuestionType::Frequency)
            ->and($question->period)->toBe(Period::Weekly)
            ->and($question->label)->toBe('How many times do I exercise per week?')
            ->and($question->active_round_id)->toBeNull();

        expect(Round::count())->toBe(0);
    });

    it('generates correct label for frequency with daily period', function () {
        $user = User::factory()->create();

        $event = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'meditate', period: Period::Daily),
        );

        $question = Question::find($event->question_id);
        expect($question->label)->toBe('How many times do I meditate per day?');
    });

    it('still auto-starts a round for duration', function () {
        $user = User::factory()->create();

        $event = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        );

        $question = Question::find($event->question_id);
        expect($question->active_round_id)->not->toBeNull();
    });

    it('accepts guess and note on frequency question', function () {
        $user = User::factory()->create();

        $event = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
            guess: '5',
            note: 'Trying to be more active',
        );

        $question = Question::find($event->question_id);
        expect($question->guess)->toBe('5');

        $noteEntry = TimelineEntry::where('question_id', $event->question_id)
            ->where('type', 'note')
            ->first();
        expect($noteEntry)->not->toBeNull()
            ->and($noteEntry->body)->toBe('Trying to be more active');
    });
});

describe('UsageLogged event', function () {
    it('creates a timeline entry of type usage_logged', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How many times do I exercise per week?',
            question_type: QuestionType::Frequency,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        ));

        verb(new UsageLogged(
            question_id: $questionEvent->question_id,
        ));
        Verbs::commit();

        $entry = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'usage_logged')
            ->first();

        expect($entry)->not->toBeNull()
            ->and($entry->occurred_at)->not->toBeNull()
            ->and($entry->recorded_at)->not->toBeNull();
    });

    it('supports backdating via occurred_at', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How many times do I exercise per week?',
            question_type: QuestionType::Frequency,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        ));

        $backdated = now()->subDays(3)->toImmutable();

        verb(new UsageLogged(
            question_id: $questionEvent->question_id,
            occurred_at: $backdated,
        ));
        Verbs::commit();

        $entry = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'usage_logged')
            ->first();

        expect($entry->occurred_at->format('Y-m-d'))->toBe($backdated->format('Y-m-d'));
    });
});

describe('LogUsage action', function () {
    it('logs usage and creates timeline entry', function () {
        $user = User::factory()->create();

        $questionEvent = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        );

        (new LogUsage)->execute(
            question_id: $questionEvent->question_id,
        );

        $entries = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'usage_logged')
            ->get();

        expect($entries)->toHaveCount(1);
    });

    it('logs usage with optional note', function () {
        $user = User::factory()->create();

        $questionEvent = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        );

        (new LogUsage)->execute(
            question_id: $questionEvent->question_id,
            note: 'Morning run',
        );

        $noteEntry = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'note')
            ->first();

        expect($noteEntry)->not->toBeNull()
            ->and($noteEntry->body)->toBe('Morning run');
    });

    it('can log multiple usages', function () {
        $user = User::factory()->create();

        $questionEvent = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        );

        (new LogUsage)->execute(question_id: $questionEvent->question_id);
        (new LogUsage)->execute(question_id: $questionEvent->question_id);
        (new LogUsage)->execute(question_id: $questionEvent->question_id);

        $entries = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'usage_logged')
            ->get();

        expect($entries)->toHaveCount(3);
    });
});

describe('RecordEntry action', function () {
    it('dispatches to LogUsage for frequency questions', function () {
        $user = User::factory()->create();

        $questionEvent = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
        );

        $result = (new RecordEntry)->execute(
            question_id: $questionEvent->question_id,
        );

        expect($result['action'])->toBe('usage_logged');

        $entries = TimelineEntry::where('question_id', $questionEvent->question_id)
            ->where('type', 'usage_logged')
            ->get();

        expect($entries)->toHaveCount(1);
    });

    it('returns start_round redirect for duration with no active round', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $result = (new RecordEntry)->execute(
            question_id: $questionEvent->question_id,
        );

        expect($result['action'])->toBe('start_round')
            ->and($result['redirect'])->toBe('start-round');
    });
});

describe('Guess comparison for frequency', function () {
    it('can update guess on a frequency question', function () {
        $user = User::factory()->create();

        $questionEvent = (new AskQuestion)->execute(
            user_id: $user->id,
            question: new FrequencyQuestion(thing: 'exercise', period: Period::Weekly),
            guess: '3',
        );

        (new UpdateGuess)->execute(
            question_id: $questionEvent->question_id,
            guess: '5',
        );

        $question = Question::find($questionEvent->question_id);
        expect($question->guess)->toBe('5');
    });
});
