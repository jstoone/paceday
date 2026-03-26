<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\TagUnlinked;
use Thunk\Verbs\Facades\Verbs;

class UnlinkTag
{
    public function execute(string $tag_id): TagUnlinked
    {
        $event = verb(new TagUnlinked(
            tag_id: $tag_id,
        ));

        Verbs::commit();

        return $event;
    }
}
