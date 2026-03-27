<?php

use App\Domain\Tracking\Actions\AskQuestion;
use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Data\FrequencyQuestion;
use App\Domain\Tracking\Period;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ask a question')] class extends Component {
    public string $question_type = 'duration';
    public string $amount = '1';
    public string $unit = '';
    public string $thing = '';
    public string $period = 'weekly';
    public ?string $guess = null;
    public ?string $note = null;

    public function ask(AskQuestion $action): void
    {
        $rules = $this->question_type === 'frequency'
            ? [
                'thing' => ['required', 'string', 'max:255'],
                'period' => ['required', 'string', 'in:daily,weekly,monthly'],
            ]
            : [
                'amount' => ['required', 'integer', 'min:1'],
                'unit' => ['required', 'string', 'max:255'],
                'thing' => ['required', 'string', 'max:255'],
            ];

        $this->validate([
            ...$rules,
            'guess' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $question = $this->question_type === 'frequency'
            ? FrequencyQuestion::from([
                'thing' => $this->thing,
                'period' => Period::from($this->period),
            ])
            : DurationQuestion::from([
                'thing' => $this->thing,
                'unit' => $this->unit,
                'amount' => (int) $this->amount,
            ]);

        $event = $action->execute(
            user_id: Auth::id(),
            question: $question,
            guess: $this->guess,
            note: $this->note,
        );

        $this->redirect(route('questions.show', $event->question_id));
    }
}; ?>

<section>
        <form wire:submit="ask" class="space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-bark">Ask a question</h1>
                <p class="mt-1 text-sm text-bark-light">Complete the sentence to start tracking.</p>
            </div>

            {{-- Question type toggle --}}
            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="$set('question_type', 'duration')"
                    class="rounded-full px-4 py-2 text-sm font-medium transition {{ $question_type === 'duration' ? 'bg-rust text-white shadow-md shadow-rust/25' : 'bg-sand text-bark-light hover:bg-zinc-200' }}"
                >
                    How long
                </button>
                <button
                    type="button"
                    wire:click="$set('question_type', 'frequency')"
                    class="rounded-full px-4 py-2 text-sm font-medium transition {{ $question_type === 'frequency' ? 'bg-rust text-white shadow-md shadow-rust/25' : 'bg-sand text-bark-light hover:bg-zinc-200' }}"
                >
                    How many
                </button>
            </div>

            {{-- Sentence builder --}}
            <div class="paceday-card"
                 x-data="{
                     resize(el) {
                         if (el.type === 'number') {
                             const len = Math.max(String(el.value).length, el.placeholder.length, 1);
                             el.style.width = (len + 1.5) + 'ch';
                         } else {
                             const len = Math.max(el.value.length, el.placeholder.length, 2);
                             el.style.width = (len + 1) + 'ch';
                         }
                     }
                 }"
            >
                @if ($question_type === 'duration')
                    <p class="sentence-builder leading-relaxed">
                        <span>How long does</span>
                        <input
                            wire:model="amount"
                            type="number"
                            min="1"
                            placeholder="40"
                            required
                            class="sentence-input"
                            x-init="resize($el)"
                            @input="resize($el)"
                        />
                        <input
                            wire:model="unit"
                            type="text"
                            placeholder="capsules"
                            required
                            class="sentence-input"
                            x-init="resize($el)"
                            @input="resize($el)"
                        />
                        <span>of</span>
                        <input
                            wire:model="thing"
                            type="text"
                            placeholder="coffee"
                            required
                            class="sentence-input"
                            x-init="resize($el)"
                            @input="resize($el)"
                        />
                        <span>last?</span>
                    </p>
                @else
                    <p class="sentence-builder leading-relaxed">
                        <span>How many times do I</span>
                        <input
                            wire:model="thing"
                            type="text"
                            placeholder="exercise"
                            required
                            class="sentence-input"
                            x-init="resize($el)"
                            @input="resize($el)"
                        />
                        <span>per</span>
                        <select
                            wire:model="period"
                            class="sentence-input !w-auto appearance-none bg-transparent cursor-pointer"
                        >
                            <option value="daily">day</option>
                            <option value="weekly">week</option>
                            <option value="monthly">month</option>
                        </select>
                        <span>?</span>
                    </p>
                @endif
            </div>

            {{-- Optional fields --}}
            <div class="paceday-card space-y-4">
                <flux:field>
                    <flux:label>My guess</flux:label>
                    <flux:input wire:model="guess" type="text" placeholder="{{ $question_type === 'frequency' ? 'e.g. 12' : 'e.g. 3 weeks' }}" />
                    <flux:description>
                        Optional — predict {{ $question_type === 'frequency' ? 'how many times' : 'how long this will take' }}.
                    </flux:description>
                    <flux:error name="guess" />
                </flux:field>

                <flux:field>
                    <flux:label>Note</flux:label>
                    <flux:textarea wire:model="note" placeholder="Any initial observations..." rows="2" />
                    <flux:error name="note" />
                </flux:field>
            </div>

            <flux:button variant="primary" type="submit" class="w-full py-3 text-base">
                Start tracking
            </flux:button>
        </form>
</section>
