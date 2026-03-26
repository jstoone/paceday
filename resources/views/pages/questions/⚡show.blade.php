<?php

use App\Domain\Tracking\Actions\AddNote;
use App\Domain\Tracking\Actions\AdjustRoundEnd;
use App\Domain\Tracking\Actions\AdjustRoundStart;
use App\Domain\Tracking\Actions\CreateTag;
use App\Domain\Tracking\Actions\EndRound;
use App\Domain\Tracking\Actions\RetireQuestion;
use App\Domain\Tracking\Actions\UnlinkTag;
use App\Domain\Tracking\Actions\UpdateGuess;
use App\Domain\Tracking\Actions\VoidRound;
use App\Models\Question;
use App\Models\Round;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Question')] class extends Component {
    public Question $question;

    public ?string $note = null;

    public ?string $occurred_at = null;

    public ?string $guess = null;

    public ?string $annotation = null;

    public function mount(string $questionId): void
    {
        $this->question = Question::with('activeRound')->findOrFail($questionId);
        $this->occurred_at = now()->format('Y-m-d');
        $this->guess = $this->question->guess;
    }

    /** @return array{round_count: int, average_days: float, consumption_rate: ?float, consumption_unit: string, average_accuracy: ?float}|null */
    #[Computed]
    public function trends(): ?array
    {
        $endedRounds = $this->question->rounds()
            ->where('status', 'ended')
            ->oldest('occurred_at')
            ->get();

        if ($endedRounds->isEmpty()) {
            return null;
        }

        $guessDays = self::parseDurationToDays($this->question->guess);

        $durations = $endedRounds->map(fn ($round) => (int) $round->occurred_at->diffInDays($round->ended_at));

        $averageDays = round($durations->avg(), 1);

        $consumptionRate = $this->question->amount > 0 && $averageDays > 0
            ? round($this->question->amount / $averageDays, 1)
            : null;

        $averageAccuracy = $guessDays !== null
            ? round($durations->avg() - $guessDays, 1)
            : null;

        return [
            'round_count' => $endedRounds->count(),
            'average_days' => $averageDays,
            'consumption_rate' => $consumptionRate,
            'consumption_unit' => $this->question->unit,
            'average_accuracy' => $averageAccuracy,
        ];
    }

    #[Computed]
    public function timeline(): array
    {
        $rounds = $this->question->rounds()
            ->whereIn('status', ['ended', 'voided'])
            ->latest('occurred_at')
            ->get();

        $notes = $this->question->timelineEntries()
            ->where('type', 'note')
            ->get();

        $guessEntries = $this->question->timelineEntries()
            ->where('type', 'guess_updated')
            ->latest('occurred_at')
            ->get();

        $retiredEntries = $this->question->timelineEntries()
            ->where('type', 'question_retired')
            ->get();

        $matchedNoteIds = collect();

        $roundItems = $rounds->map(function ($round) use ($notes, &$matchedNoteIds) {
            $roundNotes = $notes->filter(
                fn ($note) => $note->occurred_at->equalTo($round->occurred_at)
                    || ($round->ended_at && $note->occurred_at->equalTo($round->ended_at))
                    || ($round->voided_at && $note->occurred_at->equalTo($round->voided_at))
            );

            $matchedNoteIds = $matchedNoteIds->merge($roundNotes->pluck('id'));

            $sortDate = $round->voided_at ?? $round->ended_at ?? $round->occurred_at;

            return [
                'type' => 'round',
                'round' => $round,
                'notes' => $roundNotes,
                'sort_date' => $sortDate,
            ];
        });

        $standaloneNotes = $notes->reject(fn ($note) => $matchedNoteIds->contains($note->id));

        $standaloneNoteItems = $standaloneNotes->map(fn ($note) => [
            'type' => 'note',
            'entry' => $note,
            'sort_date' => $note->occurred_at,
        ]);

        $guessItems = $guessEntries->map(fn ($entry) => [
            'type' => 'guess_updated',
            'entry' => $entry,
            'sort_date' => $entry->occurred_at,
        ]);

        $retiredItems = $retiredEntries->map(fn ($entry) => [
            'type' => 'question_retired',
            'entry' => $entry,
            'sort_date' => $entry->occurred_at,
        ]);

        return $roundItems->concat($standaloneNoteItems)
            ->concat($guessItems)
            ->concat($retiredItems)
            ->sortByDesc('sort_date')
            ->values()
            ->all();
    }

    public function record(): void
    {
        if (! $this->question->activeRound) {
            $this->redirect(
                route('questions.start-round', $this->question->id),
                navigate: true,
            );

            return;
        }

        $this->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        app(EndRound::class)->execute(
            round_id: $this->question->active_round_id,
            occurred_at: CarbonImmutable::parse($this->occurred_at),
            note: $this->note,
        );

        $this->question->refresh()->load('activeRound');
        $this->note = null;
        $this->occurred_at = now()->format('Y-m-d');
        unset($this->timeline);
        unset($this->trends);
    }

    public function updateGuess(): void
    {
        $this->validate([
            'guess' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->guess === null || $this->guess === '') {
            return;
        }

        app(UpdateGuess::class)->execute(
            question_id: $this->question->id,
            guess: $this->guess,
        );

        $this->question->refresh();
        unset($this->timeline);
        unset($this->trends);
    }

    public function voidRound(string $roundId, ?string $note = null): void
    {
        app(VoidRound::class)->execute(
            round_id: $roundId,
            note: $note ?: null,
        );

        $this->question->refresh()->load('activeRound');
        unset($this->timeline);
        unset($this->trends);
    }

    public function addNote(): void
    {
        $this->validate([
            'annotation' => ['required', 'string', 'max:1000'],
        ]);

        app(AddNote::class)->execute(
            question_id: $this->question->id,
            body: $this->annotation,
        );

        $this->annotation = null;
        unset($this->timeline);
    }

    public function adjustRoundDates(string $roundId, ?string $startDate = null, ?string $endDate = null): void
    {
        $round = Round::findOrFail($roundId);

        if ($startDate && $startDate !== $round->occurred_at->format('Y-m-d')) {
            app(AdjustRoundStart::class)->execute(
                round_id: $roundId,
                new_occurred_at: CarbonImmutable::parse($startDate),
            );
        }

        if ($endDate && $round->ended_at && $endDate !== $round->ended_at->format('Y-m-d')) {
            app(AdjustRoundEnd::class)->execute(
                round_id: $roundId,
                new_ended_at: CarbonImmutable::parse($endDate),
            );
        }

        $this->question->refresh()->load('activeRound');
        unset($this->timeline);
        unset($this->trends);
    }

    public function startNewRound(): void
    {
        $this->redirect(
            route('questions.start-round', $this->question->id),
            navigate: true,
        );
    }

    public function retire(?string $note = null): void
    {
        app(RetireQuestion::class)->execute(
            question_id: $this->question->id,
            note: $note ?: null,
        );

        $this->question->refresh()->load('activeRound');
        unset($this->timeline);
        unset($this->trends);
    }

    #[Computed]
    public function tags(): Collection
    {
        return $this->question->tags()->get();
    }

    public function createTag(): void
    {
        app(CreateTag::class)->execute(
            user_id: auth()->id(),
            question_id: $this->question->id,
        );

        unset($this->tags);
    }

    public function unlinkTag(string $tagId): void
    {
        app(UnlinkTag::class)->execute(tag_id: $tagId);
        unset($this->tags);
    }

    public static function parseDurationToDays(?string $duration): ?int
    {
        if ($duration === null || $duration === '') {
            return null;
        }

        $duration = strtolower(trim($duration));

        if (preg_match('/^(\d+)\s*(day|days|d)$/', $duration, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(\d+)\s*(week|weeks|wk|wks|w)$/', $duration, $matches)) {
            return (int) $matches[1] * 7;
        }

        if (preg_match('/^(\d+)\s*(month|months|mo|mos)$/', $duration, $matches)) {
            return (int) $matches[1] * 30;
        }

        if (preg_match('/^(\d+)\s*(year|years|yr|yrs|y)$/', $duration, $matches)) {
            return (int) $matches[1] * 365;
        }

        return null;
    }
}; ?>

<section>
        <div class="space-y-6">
            {{-- Back link --}}
            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-bark-light transition hover:text-bark">
                <flux:icon.arrow-left class="size-4" />
                Back
            </a>

            {{-- Question header --}}
            <div>
                <div class="flex items-start justify-between">
                    <h1 class="text-2xl font-bold text-bark">{{ $question->label }}</h1>
                    @if ($question->retired_at)
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-bark-light">
                            Retired
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-bark-light">
                    {{ $question->amount }} {{ $question->unit }} of {{ $question->thing }}
                </p>

                {{-- Editable guess --}}
                @if ($question->retired_at)
                    @if ($question->guess)
                        <p class="mt-2 text-sm text-bark-light">
                            Guess: <span class="font-medium text-bark">{{ $question->guess }}</span>
                        </p>
                    @endif
                @else
                    <div class="mt-2" x-data="{ editing: false }">
                        <div x-show="!editing" class="flex items-center gap-2">
                            @if ($question->guess)
                                <p class="text-sm text-bark-light">
                                    Guess: <span class="font-medium text-bark">{{ $question->guess }}</span>
                                </p>
                                <button
                                    x-on:click="editing = true"
                                    type="button"
                                    class="text-xs font-medium text-rust transition hover:text-rust-dark"
                                >
                                    edit
                                </button>
                            @else
                                <button
                                    x-on:click="editing = true"
                                    type="button"
                                    class="text-sm font-medium text-rust transition hover:text-rust-dark"
                                >
                                    + Add a guess
                                </button>
                            @endif
                        </div>

                        <div x-show="editing" x-cloak class="flex items-center gap-2">
                            <flux:input
                                wire:model="guess"
                                placeholder="e.g. 3 weeks"
                                size="sm"
                                class="!w-40"
                                x-on:keydown.enter="$wire.updateGuess().then(() => editing = false)"
                            />
                            <flux:button
                                wire:click="updateGuess"
                                variant="primary"
                                size="sm"
                                x-on:click="$nextTick(() => editing = false)"
                            >
                                Save
                            </flux:button>
                            <button
                                x-on:click="editing = false; $wire.set('guess', '{{ $question->guess }}')"
                                type="button"
                                class="text-xs font-medium text-bark-light transition hover:text-bark"
                            >
                                cancel
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @unless ($question->retired_at)
                @if ($question->activeRound)
                    {{-- Active round card --}}
                    <div class="paceday-card">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                    <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                    Round in progress
                                </span>
                                <p class="text-sm text-bark-light">
                                    Started {{ $question->activeRound->occurred_at->diffForHumans() }}
                                    &middot;
                                    Day {{ (int) $question->activeRound->occurred_at->diffInDays(now()) + 1 }}
                                </p>
                            </div>

                            {{-- Day counter --}}
                            <div class="ml-4 flex flex-col items-center rounded-2xl bg-sand px-4 py-3">
                                <span class="text-3xl font-bold text-rust" style="font-family: var(--font-heading)">
                                    {{ (int) $question->activeRound->occurred_at->diffInDays(now()) + 1 }}
                                </span>
                                <span class="text-xs font-medium text-bark-light">days</span>
                            </div>
                        </div>
                    </div>

                    {{-- Recording form --}}
                    <div class="paceday-card space-y-4" x-data="{ showDetails: false }">
                        {{-- Date display / picker toggle --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-bark-light">Ended on</span>
                            <flux:date-picker wire:model="occurred_at" max="today">
                                <x-slot name="trigger">
                                    <flux:date-picker.button class="!rounded-full !bg-sand !text-bark !shadow-none !border-0 hover:!bg-zinc-200 !px-3 !py-1 !text-sm !font-medium" />
                                </x-slot>
                            </flux:date-picker>
                        </div>

                        {{-- Optional note --}}
                        <button
                            x-show="!showDetails"
                            x-on:click="showDetails = true"
                            type="button"
                            class="text-sm font-medium text-bark-light transition hover:text-bark"
                        >
                            + Add a note
                        </button>

                        <div x-show="showDetails" x-cloak>
                            <flux:textarea wire:model="note" placeholder="Any notes about this round..." rows="2" />
                        </div>

                        <flux:button wire:click="record" variant="primary" class="w-full py-3 text-base">
                            Done — ran out!
                        </flux:button>
                    </div>

                    {{-- Void active round --}}
                    <div x-data="{ confirming: false, note: '' }">
                        <button
                            x-show="!confirming"
                            x-on:click="confirming = true"
                            type="button"
                            class="text-sm font-medium text-bark-light transition hover:text-red-600"
                        >
                            Void this round
                        </button>

                        <div x-show="confirming" x-cloak class="paceday-card space-y-3">
                            <p class="text-sm font-medium text-bark">Void this round?</p>
                            <p class="text-xs text-bark-light">The round will be marked as invalid and excluded from trends.</p>
                            <textarea
                                x-model="note"
                                placeholder="Reason for voiding (optional)..."
                                rows="2"
                                class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-bark shadow-sm focus:border-amber-300 focus:ring-amber-300"
                            ></textarea>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    x-on:click="$wire.voidRound('{{ $question->active_round_id }}', note); confirming = false; note = ''"
                                    variant="danger"
                                    size="sm"
                                >
                                    Void round
                                </flux:button>
                                <button
                                    x-on:click="confirming = false; note = ''"
                                    type="button"
                                    class="text-xs font-medium text-bark-light transition hover:text-bark"
                                >
                                    cancel
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="paceday-card py-8 text-center">
                        <p class="text-bark-light">No active round</p>
                    </div>

                    <flux:button wire:click="startNewRound" class="w-full py-3 text-base !bg-teal-600 !text-white !shadow-md !shadow-teal-600/25 hover:!bg-teal-700 hover:!shadow-lg hover:!shadow-teal-600/30 hover:!-translate-y-px transition-all">
                        Start a new round
                    </flux:button>
                @endif

                {{-- Add a note --}}
                <div class="paceday-card space-y-3" x-data="{ open: false }">
                    <button
                        x-show="!open"
                        x-on:click="open = true"
                        type="button"
                        class="text-sm font-medium text-bark-light transition hover:text-bark"
                    >
                        + Add a note
                    </button>

                    <div x-show="open" x-cloak class="space-y-3">
                        <flux:textarea wire:model="annotation" placeholder="Add a note to this question..." rows="2" />
                        @error('annotation') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex items-center gap-2">
                            <flux:button
                                wire:click="addNote"
                                variant="primary"
                                size="sm"
                                x-on:click="if ($wire.annotation) $nextTick(() => open = false)"
                            >
                                Save note
                            </flux:button>
                            <button
                                x-on:click="open = false; $wire.set('annotation', null)"
                                type="button"
                                class="text-xs font-medium text-bark-light transition hover:text-bark"
                            >
                                cancel
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                <div class="paceday-card space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-bark">Tags</h3>
                        <button
                            wire:click="createTag"
                            type="button"
                            class="text-xs font-medium text-rust transition hover:text-rust-dark"
                        >
                            + New tag
                        </button>
                    </div>

                    @if ($this->tags->isEmpty())
                        <p class="text-sm text-bark-light">
                            No tags yet. Create one to record via QR code, NFC, or Shortcuts.
                        </p>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->tags as $tag)
                                <div class="rounded-2xl bg-sand p-3" wire:key="tag-{{ $tag->id }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono text-lg font-bold tracking-widest text-bark">
                                                {{ strtoupper($tag->code) }}
                                            </span>
                                            <a
                                                href="{{ route('tags.show', $tag->code) }}"
                                                class="text-xs text-bark-light transition hover:text-bark"
                                                target="_blank"
                                            >
                                                {{ route('tags.show', $tag->code) }}
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                wire:click="unlinkTag('{{ $tag->id }}')"
                                                wire:confirm="Remove this tag? The code will no longer work."
                                                type="button"
                                                class="text-xs font-medium text-bark-light transition hover:text-red-600"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Retire question --}}
                <div x-data="{ confirming: false, retireNote: '' }">
                    <button
                        x-show="!confirming"
                        x-on:click="confirming = true"
                        type="button"
                        class="text-sm font-medium text-bark-light transition hover:text-red-600"
                    >
                        Retire this question
                    </button>

                    <div x-show="confirming" x-cloak class="paceday-card space-y-3">
                        <p class="text-sm font-medium text-bark">Retire this question?</p>
                        <p class="text-xs text-bark-light">
                            The question will be hidden from your dashboard. All data is preserved and accessible via direct link.
                            @if ($question->activeRound)
                                The active round will be voided.
                            @endif
                        </p>
                        <textarea
                            x-model="retireNote"
                            placeholder="Reason for retiring (optional)..."
                            rows="2"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-bark shadow-sm focus:border-amber-300 focus:ring-amber-300"
                        ></textarea>
                        <div class="flex items-center gap-2">
                            <flux:button
                                x-on:click="$wire.retire(retireNote); confirming = false; retireNote = ''"
                                variant="danger"
                                size="sm"
                            >
                                Retire question
                            </flux:button>
                            <button
                                x-on:click="confirming = false; retireNote = ''"
                                type="button"
                                class="text-xs font-medium text-bark-light transition hover:text-bark"
                            >
                                cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- Trends --}}
            @if ($this->trends)
                <div class="paceday-card space-y-4">
                    <h2 class="text-lg font-bold text-bark">Trends</h2>

                    {{-- Key metrics --}}
                    <div class="flex items-baseline gap-6">
                        <div>
                            <span class="text-2xl font-bold text-bark" style="font-family: var(--font-heading)">
                                {{ $this->trends['average_days'] }}
                            </span>
                            <span class="text-sm text-bark-light">days avg</span>
                        </div>

                        @if ($this->trends['consumption_rate'])
                            <div>
                                <span class="text-2xl font-bold text-bark" style="font-family: var(--font-heading)">
                                    ~{{ $this->trends['consumption_rate'] }}
                                </span>
                                <span class="text-sm text-bark-light">{{ $this->trends['consumption_unit'] }}/day</span>
                            </div>
                        @endif
                    </div>

                    {{-- Guess accuracy summary --}}
                    @if ($this->trends['average_accuracy'] !== null)
                        @php $acc = $this->trends['average_accuracy']; @endphp
                        <p class="text-sm text-bark-light">
                            @if ($acc == 0)
                                <span class="font-medium text-green-600">Your guess is spot on on average!</span>
                            @elseif ($acc > 0)
                                You tend to last <span class="font-medium text-amber-600">{{ abs($acc) }} {{ Str::plural('day', (int) abs($acc)) }} longer</span> than guessed
                            @else
                                You tend to run out <span class="font-medium text-red-600">{{ abs($acc) }} {{ Str::plural('day', (int) abs($acc)) }} early</span>
                            @endif
                        </p>
                    @endif

                </div>
            @endif

            {{-- Timeline --}}
            @if (count($this->timeline) > 0)
                <div class="space-y-3">
                    <h2 class="text-lg font-bold text-bark">Timeline</h2>

                    @foreach ($this->timeline as $entry)
                        @if ($entry['type'] === 'round')
                            @php
                                $round = $entry['round'];
                                $notes = $entry['notes'];
                                $isVoided = $round->status === 'voided';
                                $days = $round->ended_at ? (int) $round->occurred_at->diffInDays($round->ended_at) : null;
                                $guessDays = self::parseDurationToDays($question->guess);
                            @endphp

                            <div
                                class="paceday-card {{ $isVoided ? 'opacity-50' : '' }}"
                                x-data="{ showActions: false, adjusting: false, voiding: false, voidNote: '', startDate: '{{ $round->occurred_at->format('Y-m-d') }}', endDate: '{{ $round->ended_at?->format('Y-m-d') }}' }"
                                wire:key="round-{{ $round->id }}"
                            >
                                {{-- Round content --}}
                                <div class="flex items-start justify-between">
                                    <div class="{{ $isVoided ? 'line-through' : '' }}">
                                        <p class="text-sm font-medium text-bark">
                                            @if ($days !== null)
                                                {{ $round->occurred_at->format('M j') }} &mdash; {{ $round->ended_at->format('M j') }}
                                            @else
                                                {{ $round->occurred_at->format('M j') }} &mdash; voided
                                            @endif
                                        </p>
                                        @if ($days !== null)
                                            <p class="mt-0.5 text-sm text-bark-light">
                                                {{ $days }} {{ Str::plural('day', $days) }}
                                            </p>
                                        @endif
                                        @if (!$isVoided && $question->guess && $guessDays !== null && $days !== null)
                                            @php
                                                $diff = $days - $guessDays;
                                            @endphp
                                            <p class="mt-1 text-xs text-bark-light">
                                                Guessed {{ $question->guess }}
                                                @if ($diff === 0)
                                                    <span class="font-medium text-green-600">&mdash; spot on!</span>
                                                @elseif ($diff > 0)
                                                    <span class="font-medium text-amber-600">&mdash; lasted {{ abs($diff) }} {{ Str::plural('day', abs($diff)) }} longer</span>
                                                @else
                                                    <span class="font-medium text-red-600">&mdash; ran out {{ abs($diff) }} {{ Str::plural('day', abs($diff)) }} early</span>
                                                @endif
                                            </p>
                                        @elseif (!$isVoided && $question->guess)
                                            <p class="mt-1 text-xs text-bark-light">
                                                Guessed {{ $question->guess }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="ml-4 flex flex-col items-center">
                                        @if ($isVoided)
                                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-bark-light">
                                                Voided
                                            </span>
                                        @elseif ($days !== null)
                                            <div class="flex flex-col items-center rounded-2xl bg-sand px-3 py-2">
                                                <span class="text-xl font-bold text-bark" style="font-family: var(--font-heading)">
                                                    {{ $days }}
                                                </span>
                                                <span class="text-[10px] font-medium text-bark-light">{{ Str::plural('day', $days) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Notes attached to this round --}}
                                @foreach ($notes as $roundNote)
                                    <p class="mt-3 border-t border-zinc-100 pt-3 text-sm text-bark-light italic">
                                        &ldquo;{{ $roundNote->body }}&rdquo;
                                    </p>
                                @endforeach

                                {{-- Actions toggle --}}
                                @if (!$isVoided)
                                    <div class="mt-3 border-t border-zinc-100 pt-3">
                                        <div class="flex items-center gap-3" x-show="!adjusting && !voiding">
                                            <button
                                                x-on:click="adjusting = true"
                                                type="button"
                                                class="text-xs font-medium text-bark-light transition hover:text-bark"
                                            >
                                                Adjust dates
                                            </button>
                                            <button
                                                x-on:click="voiding = true"
                                                type="button"
                                                class="text-xs font-medium text-bark-light transition hover:text-red-600"
                                            >
                                                Void
                                            </button>
                                        </div>

                                        {{-- Adjust dates form --}}
                                        <div x-show="adjusting" x-cloak class="space-y-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1">
                                                    <label class="text-xs font-medium text-bark-light">Started</label>
                                                    <input
                                                        type="date"
                                                        x-model="startDate"
                                                        class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-sm text-bark shadow-sm"
                                                    />
                                                </div>
                                                @if ($round->ended_at)
                                                    <div class="flex-1">
                                                        <label class="text-xs font-medium text-bark-light">Ended</label>
                                                        <input
                                                            type="date"
                                                            x-model="endDate"
                                                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-sm text-bark shadow-sm"
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <flux:button
                                                    x-on:click="$wire.adjustRoundDates('{{ $round->id }}', startDate, endDate); adjusting = false"
                                                    variant="primary"
                                                    size="sm"
                                                >
                                                    Save
                                                </flux:button>
                                                <button
                                                    x-on:click="adjusting = false; startDate = '{{ $round->occurred_at->format('Y-m-d') }}'; endDate = '{{ $round->ended_at?->format('Y-m-d') }}'"
                                                    type="button"
                                                    class="text-xs font-medium text-bark-light transition hover:text-bark"
                                                >
                                                    cancel
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Void confirmation --}}
                                        <div x-show="voiding" x-cloak class="space-y-3">
                                            <p class="text-xs text-bark-light">This round will be marked as invalid and excluded from trends.</p>
                                            <textarea
                                                x-model="voidNote"
                                                placeholder="Reason for voiding (optional)..."
                                                rows="2"
                                                class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-bark shadow-sm focus:border-amber-300 focus:ring-amber-300"
                                            ></textarea>
                                            <div class="flex items-center gap-2">
                                                <flux:button
                                                    x-on:click="$wire.voidRound('{{ $round->id }}', voidNote); voiding = false; voidNote = ''"
                                                    variant="danger"
                                                    size="sm"
                                                >
                                                    Void round
                                                </flux:button>
                                                <button
                                                    x-on:click="voiding = false; voidNote = ''"
                                                    type="button"
                                                    class="text-xs font-medium text-bark-light transition hover:text-bark"
                                                >
                                                    cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif ($entry['type'] === 'note')
                            @php
                                $noteEntry = $entry['entry'];
                            @endphp
                            <div class="flex items-center gap-3 rounded-2xl px-4 py-3" wire:key="note-{{ $noteEntry->id }}">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                                    <flux:icon.chat-bubble-left class="size-4 text-bark-light" />
                                </div>
                                <div>
                                    <p class="text-sm text-bark-light italic">
                                        &ldquo;{{ $noteEntry->body }}&rdquo;
                                    </p>
                                    <p class="text-xs text-bark-light">{{ $noteEntry->occurred_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @elseif ($entry['type'] === 'guess_updated')
                            @php
                                $guessEntry = $entry['entry'];
                            @endphp
                            <div class="flex items-center gap-3 rounded-2xl px-4 py-3" wire:key="guess-{{ $guessEntry->id }}">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-50">
                                    <flux:icon.light-bulb class="size-4 text-amber-600" />
                                </div>
                                <div>
                                    <p class="text-sm text-bark-light">
                                        Guess updated to <span class="font-medium text-bark">{{ $guessEntry->body }}</span>
                                    </p>
                                    <p class="text-xs text-bark-light">{{ $guessEntry->occurred_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @elseif ($entry['type'] === 'question_retired')
                            @php
                                $retiredEntry = $entry['entry'];
                            @endphp
                            <div class="flex items-center gap-3 rounded-2xl px-4 py-3" wire:key="retired-{{ $retiredEntry->id }}">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                                    <flux:icon.archive-box class="size-4 text-bark-light" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-bark-light">Question retired</p>
                                    <p class="text-xs text-bark-light">{{ $retiredEntry->occurred_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
</section>
