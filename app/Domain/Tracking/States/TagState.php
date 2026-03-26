<?php

namespace App\Domain\Tracking\States;

use Thunk\Verbs\State;

class TagState extends State
{
    public string $code;

    public int $user_id;

    public ?string $question_id = null;
}
