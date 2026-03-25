<?php

use App\Domain\Tracking\Actions\AskQuestion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ask a question')] class extends Component {
    public int $amount = 1;
    public string $unit = '';
    public string $thing = '';
    public ?string $guess = null;
    public ?string $note = null;

    public function ask(AskQuestion $action): void
    {
        $this->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:255'],
            'thing' => ['required', 'string', 'max:255'],
            'guess' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $event = $action->execute(
            user_id: Auth::id(),
            thing: $this->thing,
            unit: $this->unit,
            amount: $this->amount,
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

            {{-- Sentence builder — inputs styled inline as part of the sentence --}}
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
            </div>

            {{-- Optional fields --}}
            <div class="paceday-card space-y-4">
                <flux:field>
                    <flux:label>My guess</flux:label>
                    <flux:input wire:model="guess" type="text" placeholder="e.g. 3 weeks" />
                    <flux:description>Optional — predict how long this will take.</flux:description>
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
