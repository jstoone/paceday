<?php

use App\Models\Question;
use App\Models\TimelineEntry;
use App\Models\User;
use Livewire\Livewire;

it('sets guess on question when starting a round with a guess', function () {
    $user = User::factory()->create();

    // Create question without guess, end the round
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

    // Start a new round with a guess
    Livewire::actingAs($user)
        ->test('pages::questions.start-round', ['questionId' => $question->id])
        ->set('guess', '2 weeks')
        ->call('start');

    $question->refresh();

    expect($question->guess)->toBe('2 weeks');
});

it('creates a guess timeline entry when starting a round with a guess', function () {
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

    Livewire::actingAs($user)
        ->test('pages::questions.start-round', ['questionId' => $question->id])
        ->set('guess', '10 days')
        ->call('start');

    $guessEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'guess_updated')
        ->latest('id')
        ->first();

    expect($guessEntry)->not->toBeNull()
        ->and($guessEntry->body)->toBe('10 days');
});

it('starts a round without a guess just fine', function () {
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

    Livewire::actingAs($user)
        ->test('pages::questions.start-round', ['questionId' => $question->id])
        ->call('start')
        ->assertHasNoErrors();

    $question->refresh();

    expect($question->active_round_id)->not->toBeNull()
        ->and($question->guess)->toBeNull();
});
