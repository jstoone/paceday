<?php

use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Livewire\Livewire;

it('shows record button when active round exists', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Done — ran out!')
        ->assertSee('Round in progress');
});

it('ends a round when pressing the record button', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    expect($question->active_round_id)->not->toBeNull();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record')
        ->assertHasNoErrors();

    $question->refresh();

    expect($question->active_round_id)->toBeNull();

    $round = Round::where('question_id', $question->id)->first();

    expect($round->status)->toBe('ended')
        ->and($round->ended_at)->not->toBeNull()
        ->and($round->recorded_at)->not->toBeNull();
});

it('shows start new round button after ending a round', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $component = Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $component->assertSee('Start a new round')
        ->assertDontSee('Done — ran out!');
});

it('ends a round with an optional note via timeline entry', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('note', 'Ran out quicker this time')
        ->call('record')
        ->assertHasNoErrors();

    $noteEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'note')
        ->first();

    expect($noteEntry)->not->toBeNull()
        ->and($noteEntry->body)->toBe('Ran out quicker this time');
});

it('supports backdating when ending a round', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $yesterday = now()->subDay()->format('Y-m-d');

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('occurred_at', $yesterday)
        ->call('record')
        ->assertHasNoErrors();

    $round = Round::where('question_id', $question->id)->first();

    expect($round->ended_at->format('Y-m-d'))->toBe($yesterday);
});

it('shows timeline of ended rounds after ending a round', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Timeline');
});

it('shows round duration and dates in timeline', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round first
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Set exact dates on the ended round for a clean 5-day duration
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-10 12:00:00',
        'ended_at' => '2026-03-15 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSee('Timeline')
        ->assertSee('Mar 10')
        ->assertSee('Mar 15')
        ->assertSee('5 days');
});

it('shows ended rounds newest first', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End first round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Start and end second round
    Livewire::actingAs($user)
        ->test('pages::questions.start-round', ['questionId' => $question->id])
        ->call('start');

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Set known dates — older round started Mar 1, newer round started Mar 10
    $rounds = Round::where('question_id', $question->id)
        ->orderBy('created_at')
        ->get();

    $rounds[0]->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-08 12:00:00',
    ]);
    $rounds[1]->update([
        'occurred_at' => '2026-03-10 12:00:00',
        'ended_at' => '2026-03-17 12:00:00',
    ]);

    $content = $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->getContent();

    // Newer round (Mar 10) should appear before older round (Mar 1) in the HTML
    $newerPos = strpos($content, 'Mar 10');
    $olderPos = strpos($content, 'Mar 1 '); // trailing space to avoid matching Mar 10

    expect($newerPos)->toBeLessThan($olderPos);
});

it('shows notes with their associated round in the timeline', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('note', 'This brand was great')
        ->call('record');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('This brand was great');
});

it('does not show timeline section when no ended rounds exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertDontSee('Timeline');
});

it('shows add a guess link when no guess exists', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Add a guess');
});

it('shows current guess with edit link on question page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Guess:')
        ->assertSee('3 weeks')
        ->assertSee('edit');
});

it('can update the guess from the question page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('guess', '2 weeks')
        ->call('updateGuess')
        ->assertHasNoErrors();

    $question->refresh();

    expect($question->guess)->toBe('2 weeks');
});

it('creates a timeline entry when guess is updated from question page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('guess', '3 weeks')
        ->call('updateGuess');

    $guessEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'guess_updated')
        ->first();

    expect($guessEntry)->not->toBeNull()
        ->and($guessEntry->body)->toBe('3 weeks');
});

it('shows guess vs actual duration on round summaries', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Set known dates for a clean 9-day round
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-10 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Guessed 3 weeks')
        ->assertSee('ran out 12 days early');
});

it('shows spot on when guess matches actual duration', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // 21 days = 3 weeks exactly
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-22 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('spot on!');
});

it('shows guess changes on the timeline', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round to have timeline section
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Guess updated to')
        ->assertSee('3 weeks');
});

it('completes rounds fine without a guess', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Set known dates
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-10 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('9 days')
        ->assertDontSee('Guessed');
});

it('does not save empty guess', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('guess', '')
        ->call('updateGuess')
        ->assertHasNoErrors();

    $question->refresh();

    expect($question->guess)->toBeNull();

    expect(TimelineEntry::where('question_id', $question->id)
        ->where('type', 'guess_updated')
        ->exists())->toBeFalse();
});

// --- Trends ---

it('does not show trends section when no ended rounds exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertDontSee('Trends');
});

it('shows trends section with average duration after ending a round', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-21 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Trends')
        ->assertSee('20') // 20 days avg
        ->assertSee('days avg');
});

it('shows consumption rate using amount and unit', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-21 12:00:00', // 20 days → 40/20 = 2.0
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('~2')
        ->assertSee('capsules/day');
});

it('shows guess accuracy summary when guess exists', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // 14 days — 7 days shorter than 3 weeks (21 days)
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-15 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('run out')
        ->assertSee('7 days early');
});

it('shows trends with a single ended round without crashing', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-21 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Trends')
        ->assertSee('days avg');
});

it('excludes voided rounds from trends', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End first round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $round = Round::where('question_id', $question->id)->where('status', 'ended')->first();
    $round->update([
        'occurred_at' => '2026-01-01 12:00:00',
        'ended_at' => '2026-01-21 12:00:00',
        'status' => 'voided',
    ]);

    // No ended rounds remain (voided is excluded), so no trends section
    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertDontSee('Trends');
});

it('shows longer than guessed in accuracy summary', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '2 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // 21 days — 7 days longer than 2 weeks (14 days)
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-22 12:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('last')
        ->assertSee('7 days longer');
});

it('redirects to start-round when no active round and record is called', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round first
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Now try to record again with no active round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record')
        ->assertRedirect(route('questions.start-round', $question->id));
});
