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
    <x-layouts::app>
        <div class="mx-auto max-w-2xl px-4 py-12">
            <form wire:submit="ask" class="space-y-10">
                <div class="space-y-2">
                    <flux:heading size="xl">Ask a question</flux:heading>
                    <flux:text>Track how long something lasts by completing the sentence below.</flux:text>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="flex flex-wrap items-baseline gap-x-2 gap-y-3 text-2xl font-medium tracking-tight text-zinc-900 dark:text-white">
                        <span>How long does</span>
                        <flux:input
                            wire:model="amount"
                            type="number"
                            min="1"
                            class="!w-20 text-center text-2xl"
                            placeholder="40"
                            required
                        />
                        <flux:input
                            wire:model="unit"
                            type="text"
                            class="!w-36 text-center text-2xl"
                            placeholder="capsules"
                            required
                        />
                        <span>of</span>
                        <flux:input
                            wire:model="thing"
                            type="text"
                            class="!w-40 text-center text-2xl"
                            placeholder="coffee"
                            required
                        />
                        <span>last?</span>
                    </p>
                </div>

                <div class="space-y-4">
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

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" class="w-full sm:w-auto">
                        Start tracking
                    </flux:button>
                </div>
            </form>
        </div>
    </x-layouts::app>
</section>
