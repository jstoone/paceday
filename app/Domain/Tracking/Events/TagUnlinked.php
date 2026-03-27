<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\TagState;
use App\Models\Tag;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Event;

class TagUnlinked extends Event
{
    public function __construct(
        #[StateUlid(TagState::class)]
        public string $tag_id,
    ) {}

    public function validateTag(TagState $state): void
    {
        $this->assert(
            $state->question_id !== null,
            'Tag is not linked to a question.',
        );
    }

    public function apply(TagState $state): void
    {
        $state->question_id = null;
    }

    public function handle(): void
    {
        Tag::where('id', $this->tag_id)->update([
            'question_id' => null,
        ]);
    }
}
