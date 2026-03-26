<?php

use App\Domain\Tracking\Actions\EndRound;
use App\Domain\Tracking\Actions\StartRound;
use App\Domain\Tracking\States\QuestionState;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Record')] #[Layout('layouts.tag')] class extends Component {
    public ?Tag $tag = null;

    public ?string $note = null;

    public bool $recorded = false;

    public ?string $recordedAction = null;

    public function mount(string $code): void
    {
        $this->tag = Tag::with('question.activeRound')
            ->where('code', $code)
            ->first();
    }

    public function record(): void
    {
        if (! $this->tag || ! $this->tag->question_id) {
            return;
        }

        $question = $this->tag->question;
        $questionState = QuestionState::load($question->id);

        if ($questionState->question_type !== 'how_long') {
            return;
        }

        if ($questionState->active_round_id !== null) {
            app(EndRound::class)->execute(
                round_id: $questionState->active_round_id,
                occurred_at: CarbonImmutable::now(),
                note: $this->note,
            );

            $this->recorded = true;
            $this->recordedAction = 'round_ended';
        } else {
            app(StartRound::class)->execute(
                question_id: $question->id,
                note: $this->note,
            );

            $this->recorded = true;
            $this->recordedAction = 'round_started';
        }

        $this->tag->refresh()->load('question.activeRound');
    }
}; ?>

<section>
    <div class="space-y-6">
        @if (! $tag || ! $tag->question_id)
            {{-- Invalid or unlinked tag --}}
            <div class="paceday-card py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100">
                        <flux:icon.exclamation-triangle class="size-6 text-bark-light" />
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-bark">Tag not found</h1>
                        <p class="mt-1 text-sm text-bark-light">This tag doesn't exist or isn't linked to a question.</p>
                    </div>
                </div>
            </div>
        @elseif ($recorded)
            {{-- Success state --}}
            <div class="paceday-card py-8 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full bg-green-50">
                        <flux:icon.check-circle class="size-6 text-green-600" />
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-bark">
                            {{ $recordedAction === 'round_started' ? 'Round started!' : 'Recorded!' }}
                        </h1>
                        <p class="mt-1 text-sm text-bark-light">{{ $tag->question->label }}</p>
                    </div>
                </div>
            </div>
        @else
            {{-- Confirmation page --}}
            <div class="paceday-card space-y-4">
                <h1 class="text-xl font-bold text-bark">{{ $tag->question->label }}</h1>

                {{-- Round status --}}
                @if ($tag->question->activeRound)
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                            <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            Day {{ (int) $tag->question->activeRound->occurred_at->diffInDays(now()) + 1 }}
                        </span>
                        <span class="text-sm text-bark-light">
                            Started {{ $tag->question->activeRound->occurred_at->diffForHumans() }}
                        </span>
                    </div>
                @else
                    <p class="text-sm text-bark-light">No active round</p>
                @endif
            </div>

            {{-- Record form --}}
            <div class="paceday-card space-y-4" x-data="{ showNote: false }">
                <button
                    x-show="!showNote"
                    x-on:click="showNote = true"
                    type="button"
                    class="text-sm font-medium text-bark-light transition hover:text-bark"
                >
                    + Add a note
                </button>

                <div x-show="showNote" x-cloak>
                    <flux:textarea wire:model="note" placeholder="Any notes..." rows="2" />
                </div>

                @if ($tag->question->activeRound)
                    <flux:button wire:click="record" variant="primary" class="w-full py-3 text-base">
                        Done — ran out!
                    </flux:button>
                @else
                    <flux:button wire:click="record" class="w-full py-3 text-base !bg-teal-600 !text-white !shadow-md !shadow-teal-600/25 hover:!bg-teal-700 hover:!shadow-lg hover:!shadow-teal-600/30 hover:!-translate-y-px transition-all">
                        Start a new round
                    </flux:button>
                @endif
            </div>
        @endif
    </div>
</section>
