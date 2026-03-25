<?php

use App\Models\Question;
use App\Models\Round;
use App\Models\TimelineEntry;
use App\Models\User;
use Livewire\Livewire;

it('shows the create question page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('questions.create'))
        ->assertSuccessful();
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('questions.create'))
        ->assertRedirect(route('login'));
});

it('creates a question and round when submitting valid data', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->call('ask')
        ->assertHasNoErrors()
        ->assertRedirect();

    $question = Question::where('user_id', $user->id)->first();

    expect($question)->not->toBeNull()
        ->and($question->label)->toBe('How long does 40 capsules of coffee last?')
        ->and($question->thing)->toBe('coffee')
        ->and($question->unit)->toBe('capsules')
        ->and($question->amount)->toBe(40);

    $round = Round::where('question_id', $question->id)->first();

    expect($round)->not->toBeNull()
        ->and($round->status)->toBe('active');
});

it('creates a question with guess and note as separate events', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', 40)
        ->set('guess', '3 weeks')
        ->set('note', 'Nespresso pods')
        ->call('ask')
        ->assertHasNoErrors()
        ->assertRedirect();

    $question = Question::where('user_id', $user->id)->first();

    expect($question->guess)->toBe('3 weeks');

    $noteEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'note')
        ->first();

    expect($noteEntry)->not->toBeNull()
        ->and($noteEntry->body)->toBe('Nespresso pods');

    $guessEntry = TimelineEntry::where('question_id', $question->id)
        ->where('type', 'guess_updated')
        ->first();

    expect($guessEntry)->not->toBeNull()
        ->and($guessEntry->body)->toBe('3 weeks');
});

it('validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', '')
        ->set('unit', '')
        ->set('amount', 0)
        ->call('ask')
        ->assertHasErrors(['thing', 'unit', 'amount']);
});

it('validates amount is a positive integer', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::questions.create')
        ->set('thing', 'coffee')
        ->set('unit', 'capsules')
        ->set('amount', -5)
        ->call('ask')
        ->assertHasErrors(['amount']);
});

it('shows the question page with active round', function () {
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
        ->assertSee('How long does 40 capsules of coffee last?')
        ->assertSee('Round in progress');
});
