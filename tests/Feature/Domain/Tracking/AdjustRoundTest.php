<?php

use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundEndAdjusted;
use App\Domain\Tracking\Events\RoundEnded;
use App\Domain\Tracking\Events\RoundStartAdjusted;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\States\RoundState;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

it('adjusts the start date of a round', function () {
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
        occurred_at: CarbonImmutable::parse('2026-03-10'),
    ));
    Verbs::commit();

    $newDate = CarbonImmutable::parse('2026-03-08');

    verb(new RoundStartAdjusted(
        round_id: $roundEvent->round_id,
        old_occurred_at: CarbonImmutable::parse('2026-03-10'),
        new_occurred_at: $newDate,
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);
    expect($roundState->occurred_at->format('Y-m-d'))->toBe('2026-03-08');
});

it('projects adjusted start date to eloquent model', function () {
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
        occurred_at: CarbonImmutable::parse('2026-03-10'),
    ));
    Verbs::commit();

    verb(new RoundStartAdjusted(
        round_id: $roundEvent->round_id,
        old_occurred_at: CarbonImmutable::parse('2026-03-10'),
        new_occurred_at: CarbonImmutable::parse('2026-03-08'),
    ));
    Verbs::commit();

    $round = Round::find($roundEvent->round_id);
    expect($round->occurred_at->format('Y-m-d'))->toBe('2026-03-08');
});

it('updates the round_started timeline entry when start date is adjusted', function () {
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
        occurred_at: CarbonImmutable::parse('2026-03-10'),
    ));
    Verbs::commit();

    verb(new RoundStartAdjusted(
        round_id: $roundEvent->round_id,
        old_occurred_at: CarbonImmutable::parse('2026-03-10'),
        new_occurred_at: CarbonImmutable::parse('2026-03-08'),
    ));
    Verbs::commit();

    $entry = TimelineEntry::where('type', 'round_started')
        ->whereJsonContains('metadata->round_id', $roundEvent->round_id)
        ->first();

    expect($entry->occurred_at->format('Y-m-d'))->toBe('2026-03-08');
});

it('adjusts the end date of an ended round', function () {
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
        occurred_at: CarbonImmutable::parse('2026-03-01'),
    ));
    Verbs::commit();

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
        occurred_at: CarbonImmutable::parse('2026-03-10'),
    ));
    Verbs::commit();

    verb(new RoundEndAdjusted(
        round_id: $roundEvent->round_id,
        old_ended_at: CarbonImmutable::parse('2026-03-10'),
        new_ended_at: CarbonImmutable::parse('2026-03-12'),
    ));
    Verbs::commit();

    $roundState = RoundState::load($roundEvent->round_id);
    expect($roundState->ended_at->format('Y-m-d'))->toBe('2026-03-12');

    $round = Round::find($roundEvent->round_id);
    expect($round->ended_at->format('Y-m-d'))->toBe('2026-03-12');
});

it('prevents adjusting end date of a round that has not ended', function () {
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

    verb(new RoundEndAdjusted(
        round_id: $roundEvent->round_id,
        old_ended_at: now()->toImmutable(),
        new_ended_at: now()->addDay()->toImmutable(),
    ));
})->throws(Exception::class, 'Cannot adjust end date of a round that has not ended.');

it('updates the round_ended timeline entry when end date is adjusted', function () {
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
        occurred_at: CarbonImmutable::parse('2026-03-01'),
    ));
    Verbs::commit();

    verb(new RoundEnded(
        round_id: $roundEvent->round_id,
        question_id: $questionEvent->question_id,
        occurred_at: CarbonImmutable::parse('2026-03-10'),
    ));
    Verbs::commit();

    verb(new RoundEndAdjusted(
        round_id: $roundEvent->round_id,
        old_ended_at: CarbonImmutable::parse('2026-03-10'),
        new_ended_at: CarbonImmutable::parse('2026-03-12'),
    ));
    Verbs::commit();

    $entry = TimelineEntry::where('type', 'round_ended')
        ->whereJsonContains('metadata->round_id', $roundEvent->round_id)
        ->first();

    expect($entry->occurred_at->format('Y-m-d'))->toBe('2026-03-12');
});
