<?php

use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Livewire\Livewire;

// --- Void Round ---

it('can void an active round from the question page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();
    $roundId = $question->active_round_id;

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('voidRound', $roundId)
        ->assertHasNoErrors();

    $round = Round::find($roundId);
    expect($round->status)->toBe('voided')
        ->and($round->voided_at)->not->toBeNull();

    $question->refresh();
    expect($question->active_round_id)->toBeNull();
});

it('can void an active round with a note', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();
    $roundId = $question->active_round_id;

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('voidRound', $roundId, 'Accidentally started')
        ->assertHasNoErrors();

    $noteEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'note')
        ->first();

    expect($noteEntry)->not->toBeNull()
        ->and($noteEntry->body)->toBe('Accidentally started');
});

it('can void an ended round from the timeline', function () {
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
    expect($round->status)->toBe('ended');

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('voidRound', $round->id, 'Bad data')
        ->assertHasNoErrors();

    $round->refresh();
    expect($round->status)->toBe('voided');
});

it('shows voided rounds on the timeline visually distinct', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-10 12:00:00',
    ]);

    // Void it
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('voidRound', $round->id);

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Voided')
        ->assertSee('Timeline');
});

it('shows void note on the voided round timeline entry', function () {
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

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('voidRound', $round->id, 'Wrong product');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Wrong product');
});

// --- Adjust Dates ---

it('can adjust the start date of a round', function () {
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
        'occurred_at' => '2026-03-10 12:00:00',
        'ended_at' => '2026-03-20 12:00:00',
    ]);

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('adjustRoundDates', $round->id, '2026-03-08', '2026-03-20')
        ->assertHasNoErrors();

    $round->refresh();
    expect($round->occurred_at->format('Y-m-d'))->toBe('2026-03-08');
});

it('can adjust the end date of a round', function () {
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
        'ended_at' => '2026-03-10 12:00:00',
    ]);

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('adjustRoundDates', $round->id, '2026-03-01', '2026-03-15')
        ->assertHasNoErrors();

    $round->refresh();
    expect($round->ended_at->format('Y-m-d'))->toBe('2026-03-15');
});

it('recalculates duration after date adjustment', function () {
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
        'occurred_at' => '2026-03-01 00:00:00',
        'ended_at' => '2026-03-10 00:00:00',
    ]);

    // Adjust end date to make it 20 days
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('adjustRoundDates', $round->id, '2026-03-01', '2026-03-21');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('20 days');
});

// --- Add Note ---

it('can add a standalone note to a question', function () {
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
        ->set('annotation', 'Store brand is garbage, go back to Nespresso')
        ->call('addNote')
        ->assertHasNoErrors();

    $noteEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'note')
        ->first();

    expect($noteEntry)->not->toBeNull()
        ->and($noteEntry->body)->toBe('Store brand is garbage, go back to Nespresso');
});

it('shows standalone notes on the timeline', function () {
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
        ->set('annotation', 'Switched to decaf')
        ->call('addNote');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Switched to decaf')
        ->assertSee('Timeline');
});

it('can add a note whether or not a round is active', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round so there's no active round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Add a note with no active round
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->set('annotation', 'Thinking about trying a new brand')
        ->call('addNote')
        ->assertHasNoErrors();

    $noteEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'note')
        ->latest('id')
        ->first();

    expect($noteEntry->body)->toBe('Thinking about trying a new brand');
});

it('validates that annotation is required when adding a note', function () {
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
        ->set('annotation', '')
        ->call('addNote')
        ->assertHasErrors(['annotation' => 'required']);
});

it('clears annotation field after adding a note', function () {
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
        ->set('annotation', 'A note')
        ->call('addNote')
        ->assertSet('annotation', null);
});

// --- Trends exclude voided ---

it('excludes voided rounds from trends calculations', function () {
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

    // Void it
    $round = Round::where('question_id', $question->id)->where('status', 'ended')->first();
    $round->update([
        'occurred_at' => '2026-01-01 12:00:00',
        'ended_at' => '2026-01-21 12:00:00',
        'status' => 'voided',
        'voided_at' => now(),
    ]);

    // Start and end a second round
    Livewire::actingAs($user)
        ->test('pages::questions.start-round', ['questionId' => $question->id])
        ->call('start');

    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    $endedRound = Round::where('question_id', $question->id)->where('status', 'ended')->first();
    $endedRound->update([
        'occurred_at' => '2026-02-01 12:00:00',
        'ended_at' => '2026-02-11 12:00:00',
    ]);

    // Trends should only reflect the non-voided round (10 days)
    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('10')
        ->assertSee('days avg');
});
