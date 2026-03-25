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
    <x-layouts::app>
        <div class="mx-auto max-w-2xl px-4 py-12">
            <div class="space-y-8">
                <div class="space-y-2">
                    <flux:heading size="xl">{{ $question->label }}</flux:heading>
                    <flux:text>
                        Tracking {{ $question->amount }} {{ $question->unit }} of {{ $question->thing }}
                    </flux:text>
                </div>

                @if ($question->activeRound)
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <flux:heading>Round in progress</flux:heading>
                                <flux:text>
                                    Started {{ $question->activeRound->occurred_at->diffForHumans() }}
                                    &middot;
                                    Day {{ (int) $question->activeRound->occurred_at->diffInDays(now()) + 1 }}
                                </flux:text>
                                @if ($question->activeRound->guess)
                                    <flux:text>
                                        Guess: {{ $question->activeRound->guess }}
                                    </flux:text>
                                @endif
                                @if ($question->activeRound->note)
                                    <flux:text class="italic">
                                        {{ $question->activeRound->note }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    </div>

                    <flux:button variant="primary" class="w-full bg-red-500 hover:bg-red-600 text-lg py-4" disabled>
                        Done
                    </flux:button>
                    <flux:text class="text-center text-sm">
                        The record button will be functional in the next update.
                    </flux:text>
                @else
                    <flux:button variant="primary" class="w-full text-lg py-4" disabled>
                        Start a new round
                    </flux:button>
                @endif
            </div>
        </div>
    </x-layouts::app>
</section>
