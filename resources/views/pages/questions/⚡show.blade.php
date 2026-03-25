<?php

use App\Domain\Tracking\Actions\EndRound;
use App\Domain\Tracking\Actions\UpdateGuess;
use App\Models\Question;
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

    /** @return array<int, array{round: \App\Models\Round, notes: Collection}> */
    #[Computed]
    public function timeline(): array
    {
        $rounds = $this->question->rounds()
            ->where('status', 'ended')
            ->latest('occurred_at')
            ->get();

        $notes = $this->question->timelineEntries()
            ->where('type', 'note')
            ->get();

        $guessEntries = $this->question->timelineEntries()
            ->where('type', 'guess_updated')
            ->latest('occurred_at')
            ->get();

        $roundItems = $rounds->map(function ($round) use ($notes) {
            $roundNotes = $notes->filter(
                fn ($note) => $note->occurred_at->equalTo($round->occurred_at)
                    || ($round->ended_at && $note->occurred_at->equalTo($round->ended_at))
            );

            return [
                'type' => 'round',
                'round' => $round,
                'notes' => $roundNotes,
                'sort_date' => $round->ended_at ?? $round->occurred_at,
            ];
        });

        $guessItems = $guessEntries->map(fn ($entry) => [
            'type' => 'guess_updated',
            'entry' => $entry,
            'sort_date' => $entry->occurred_at,
        ]);

        $merged = $roundItems->concat($guessItems)
            ->sortByDesc('sort_date')
            ->values()
            ->all();

        return $merged;
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

    public function startNewRound(): void
    {
        $this->redirect(
            route('questions.start-round', $this->question->id),
            navigate: true,
        );
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
                <h1 class="text-2xl font-bold text-bark">{{ $question->label }}</h1>
                <p class="mt-1 text-sm text-bark-light">
                    {{ $question->amount }} {{ $question->unit }} of {{ $question->thing }}
                </p>

                {{-- Editable guess --}}
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
            </div>

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
            @else
                <div class="paceday-card py-8 text-center">
                    <p class="text-bark-light">No active round</p>
                </div>

                <flux:button wire:click="startNewRound" class="w-full py-3 text-base !bg-teal-600 !text-white !shadow-md !shadow-teal-600/25 hover:!bg-teal-700 hover:!shadow-lg hover:!shadow-teal-600/30 hover:!-translate-y-px transition-all">
                    Start a new round
                </flux:button>
            @endif

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
                    <h2 class="text-lg font-bold text-bark">Previous rounds</h2>

                    @foreach ($this->timeline as $entry)
                        @if ($entry['type'] === 'round')
                            @php
                                $round = $entry['round'];
                                $notes = $entry['notes'];
                                $days = (int) $round->occurred_at->diffInDays($round->ended_at);
                                $guessDays = self::parseDurationToDays($question->guess);
                            @endphp

                            <div class="paceday-card">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-bark">
                                            {{ $round->occurred_at->format('M j') }} &mdash; {{ $round->ended_at->format('M j') }}
                                        </p>
                                        <p class="mt-0.5 text-sm text-bark-light">
                                            {{ $days }} {{ Str::plural('day', $days) }}
                                        </p>
                                        @if ($question->guess && $guessDays !== null)
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
                                        @elseif ($question->guess)
                                            <p class="mt-1 text-xs text-bark-light">
                                                Guessed {{ $question->guess }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="ml-4 flex flex-col items-center rounded-2xl bg-sand px-3 py-2">
                                        <span class="text-xl font-bold text-bark" style="font-family: var(--font-heading)">
                                            {{ $days }}
                                        </span>
                                        <span class="text-[10px] font-medium text-bark-light">{{ Str::plural('day', $days) }}</span>
                                    </div>
                                </div>

                                @foreach ($notes as $note)
                                    <p class="mt-3 border-t border-zinc-100 pt-3 text-sm text-bark-light italic">
                                        &ldquo;{{ $note->body }}&rdquo;
                                    </p>
                                @endforeach
                            </div>
                        @elseif ($entry['type'] === 'guess_updated')
                            @php
                                $guessEntry = $entry['entry'];
                            @endphp
                            <div class="flex items-center gap-3 rounded-2xl px-4 py-3">
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
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
</section>
