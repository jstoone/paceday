<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\TagCreated;
use App\Domain\Tracking\Events\TagLinked;
use App\Models\Tag;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;

class CreateTag
{
    public function execute(
        int $user_id,
        ?string $question_id = null,
    ): Tag {
        $code = $this->generateUniqueCode();

        $event = verb(new TagCreated(
            code: $code,
            user_id: $user_id,
        ));

        if ($question_id !== null) {
            verb(new TagLinked(
                tag_id: $event->tag_id,
                question_id: $question_id,
            ));
        }

        Verbs::commit();

        return Tag::find($event->tag_id);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtolower(Str::random(4));
        } while (Tag::where('code', $code)->exists());

        return $code;
    }
}
