<?php

use App\Domain\Tracking\Actions\EndRound;
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

    public function mount(string $questionId): void
    {
        $this->question = Question::with('activeRound')->findOrFail($questionId);
        $this->occurred_at = now()->format('Y-m-d');
    }

    /** @return array<int, array{round: \App\Models\Round, notes: Collection}> */
    #[Computed]
    public function timeline(): array
    {
        $rounds = $this->question->rounds()
            ->where('status', 'ended')
            ->latest('occurred_at')
            ->get();

        if ($rounds->isEmpty()) {
            return [];
        }

        $notes = $this->question->timelineEntries()
            ->where('type', 'note')
            ->get();

        return $rounds->map(function ($round) use ($notes) {
            $roundNotes = $notes->filter(
                fn ($note) => $note->occurred_at->equalTo($round->occurred_at)
                    || ($round->ended_at && $note->occurred_at->equalTo($round->ended_at))
            );

            return [
                'round' => $round,
                'notes' => $roundNotes,
            ];
        })->all();
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
    }

    public function startNewRound(): void
    {
        $this->redirect(
            route('questions.start-round', $this->question->id),
            navigate: true,
        );
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
                @if ($question->guess)
                    <p class="mt-1 text-sm text-bark-light">
                        Guess: <span class="font-medium text-bark">{{ $question->guess }}</span>
                    </p>
                @endif
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

            {{-- Round timeline --}}
            @if (count($this->timeline) > 0)
                <div class="space-y-3">
                    <h2 class="text-lg font-bold text-bark">Previous rounds</h2>

                    @foreach ($this->timeline as $entry)
                        @php
                            $round = $entry['round'];
                            $notes = $entry['notes'];
                            $days = (int) $round->occurred_at->diffInDays($round->ended_at);
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
                    @endforeach
                </div>
            @endif
        </div>
</section>
