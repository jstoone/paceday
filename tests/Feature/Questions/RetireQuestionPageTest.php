<?php

use App\Models\Question;
use App\Models\Round;
use App\Models\User;
use Livewire\Livewire;

it('shows retire button on the question page', function () {
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
        ->assertSee('Retire this question');
});

it('retires a question from the question page', function () {
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
        ->call('retire')
        ->assertHasNoErrors();

    $question->refresh();

    expect($question->retired_at)->not->toBeNull();
});

it('hides retired questions from the dashboard', function () {
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
        ->call('retire');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee($question->label);
});

it('shows retired indicator on question page after retiring', function () {
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
        ->call('retire');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Retired');
});

it('hides recording actions on retired question page', function () {
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
        ->call('retire');

    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertDontSee('Done — ran out!')
        ->assertDontSee('Start a new round')
        ->assertDontSee('Retire this question');
});

it('voids active round when retiring a question with active round', function () {
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
        ->call('retire')
        ->assertHasNoErrors();

    $question->refresh();
    $round = Round::where('question_id', $question->id)->first();

    expect($question->retired_at)->not->toBeNull()
        ->and($question->active_round_id)->toBeNull()
        ->and($round->status)->toBe('voided');
});

it('preserves historical data on retired questions', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->call('ask');

    $question = Question::where('user_id', $user->id)->first();

    // End the round to create history
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('record');

    // Set known dates
    $round = Round::where('question_id', $question->id)->first();
    $round->update([
        'occurred_at' => '2026-03-01 12:00:00',
        'ended_at' => '2026-03-10 12:00:00',
    ]);

    // Now retire
    Livewire::actingAs($user)
        ->test('pages::questions.show', ['questionId' => $question->id])
        ->call('retire');

    // All data should still be visible on the question page
    $this->actingAs($user)
        ->get(route('questions.show', $question->id))
        ->assertSuccessful()
        ->assertSee('Retired')
        ->assertSee('Timeline')
        ->assertSee('Mar 1')
        ->assertSee('Mar 10')
        ->assertSee('Guess:')
        ->assertSee('3 weeks');
});
