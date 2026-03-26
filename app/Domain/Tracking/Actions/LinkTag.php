<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\TagLinked;
use Thunk\Verbs\Facades\Verbs;

class LinkTag
{
    public function execute(
        string $tag_id,
        string $question_id,
    ): TagLinked {
        $event = verb(new TagLinked(
            tag_id: $tag_id,
            question_id: $question_id,
        ));

        Verbs::commit();

        return $event;
    }
}
