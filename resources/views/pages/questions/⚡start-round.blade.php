<?php

use App\Domain\Tracking\Actions\StartRound;
use App\Models\Question;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Start a new round')] class extends Component {
    public Question $question;

    public ?string $guess = null;

    public ?string $note = null;

    public function mount(string $questionId): void
    {
        $this->question = Question::with('activeRound')->findOrFail($questionId);

        if ($this->question->activeRound) {
            $this->redirect(
                route('questions.show', $this->question->id),
                navigate: true,
            );
        }
    }

    public function start(): void
    {
        $this->validate([
            'guess' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        app(StartRound::class)->execute(
            question_id: $this->question->id,
            guess: $this->guess,
            note: $this->note,
        );

        $this->redirect(
            route('questions.show', $this->question->id),
            navigate: true,
        );
    }
}; ?>

<section>
        <div class="space-y-6">
            {{-- Back link --}}
            <a href="{{ route('questions.show', $question->id) }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-bark-light transition hover:text-bark">
                <flux:icon.arrow-left class="size-4" />
                Back to question
            </a>

            {{-- Question header --}}
            <div>
                <h1 class="text-2xl font-bold text-bark">New round</h1>
                <p class="mt-1 text-sm text-bark-light">{{ $question->label }}</p>
            </div>

            <div class="paceday-card space-y-4">
                <flux:field>
                    <flux:label>Guess (optional)</flux:label>
                    <flux:input wire:model="guess" placeholder="e.g. 3 weeks" />
                    <flux:error name="guess" />
                </flux:field>

                <flux:field>
                    <flux:label>Note (optional)</flux:label>
                    <flux:textarea wire:model="note" placeholder="Any notes for this round..." rows="2" />
                    <flux:error name="note" />
                </flux:field>

                <flux:button wire:click="start" variant="primary" class="w-full py-3 text-base">
                    Start round
                </flux:button>
            </div>
        </div>
</section>
