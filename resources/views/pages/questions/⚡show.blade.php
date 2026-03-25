<?php

use App\Models\Question;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Question')] class extends Component {
    public Question $question;

    public function mount(string $questionId): void
    {
        $this->question = Question::with('activeRound')->findOrFail($questionId);
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
                            @if ($question->activeRound->guess)
                                <p class="text-sm text-bark-light">
                                    Guess: <span class="font-medium text-bark">{{ $question->activeRound->guess }}</span>
                                </p>
                            @endif
                            @if ($question->activeRound->note)
                                <p class="text-sm italic text-bark-light">
                                    {{ $question->activeRound->note }}
                                </p>
                            @endif
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

                <flux:button variant="primary" class="w-full py-3 text-base" disabled>
                    Done — ran out!
                </flux:button>
                <p class="text-center text-xs text-bark-light">
                    The record button will be functional in the next update.
                </p>
            @else
                <div class="paceday-card py-8 text-center">
                    <p class="text-bark-light">No active round</p>
                </div>

                <flux:button variant="primary" class="w-full py-3 text-base" disabled>
                    Start a new round
                </flux:button>
            @endif
        </div>
</section>
