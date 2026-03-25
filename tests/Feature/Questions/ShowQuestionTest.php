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
