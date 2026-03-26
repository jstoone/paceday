<?php

namespace App\Domain\Tracking\Events;

use App\Domain\Tracking\States\TagState;
use App\Models\Tag;
use App\Support\Verbs\StateUlid;
use Thunk\Verbs\Attributes\Hooks\Once;
use Thunk\Verbs\Event;

class TagCreated extends Event
{
    public function __construct(
        #[StateUlid(TagState::class)]
        public ?string $tag_id = null,
        public string $code = '',
        public int $user_id = 0,
    ) {}

    public function apply(TagState $state): void
    {
        $state->code = $this->code;
        $state->user_id = $this->user_id;
    }

    #[Once]
    public function handle(): void
    {
        Tag::create([
            'id' => $this->tag_id,
            'code' => $this->code,
            'user_id' => $this->user_id,
        ]);
    }
}
