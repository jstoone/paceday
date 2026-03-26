<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\TagState;
use App\Models\Tag;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class TagLinked extends Event
{
    public function __construct(
        #[StateUlid(TagState::class)]
        public string $tag_id,
        public string $question_id,
    ) {}

    public function validateTag(TagState $state): void
    {
        $this->assert(
            $state->question_id === null,
            'Tag is already linked to a question. Unlink it first.',
        );
    }

    public function apply(TagState $state): void
    {
        $state->question_id = $this->question_id;
    }

    #[Once]
    public function handle(): void
    {
        Tag::where('id', $this->tag_id)->update([
            'question_id' => $this->question_id,
        ]);
    }
}
