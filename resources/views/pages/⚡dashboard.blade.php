<?php

use App\Models\Question;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function with(): array
    {
        return [
            'questions' => Question::query()
                ->where('user_id', Auth::id())
                ->whereNull('retired_at')
                ->with('activeRound')
                ->latest()
                ->get(),
        ];
    }
}; ?>

<section>
        <div class="space-y-6">
            {{-- Header --}}
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-bark">My questions</h1>
                    <p class="mt-1 text-sm text-bark-light">Track how long things last</p>
                </div>
                <a href="{{ route('questions.create') }}" wire:navigate>
                    <flux:button variant="primary" icon="plus" size="sm">
                        New
                    </flux:button>
                </a>
            </div>

            {{-- Questions list --}}
            @if ($questions->isEmpty())
                <div class="paceday-card py-12 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-sand">
                        <flux:icon.plus-circle class="size-8 text-zinc-400" />
                    </div>
                    <h2 class="text-lg font-semibold text-bark">No questions yet</h2>
                    <p class="mt-1 text-sm text-bark-light">Start tracking something to see how long it lasts.</p>
                    <div class="mt-6">
                        <a href="{{ route('questions.create') }}" wire:navigate>
                            <flux:button variant="primary">
                                Ask your first question
                            </flux:button>
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($questions as $question)
                        <a
                            href="{{ route('questions.show', $question->id) }}"
                            wire:navigate
                            class="paceday-card block transition hover:shadow-xl hover:-translate-y-0.5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <h2 class="truncate text-lg font-semibold text-bark">
                                        {{ $question->label }}
                                    </h2>
                                    <p class="mt-0.5 text-sm text-bark-light">
                                        {{ $question->amount }} {{ $question->unit }} of {{ $question->thing }}
                                    </p>
                                </div>

                                @if ($question->activeRound)
                                    <div class="ml-4 flex flex-col items-end">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                            <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            Day {{ (int) $question->activeRound->occurred_at->diffInDays(now()) + 1 }}
                                        </span>
                                        @if ($question->guess)
                                            <span class="mt-1 text-xs text-bark-light">
                                                Guess: {{ $question->guess }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
</section>
