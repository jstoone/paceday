<?php

namespace App\Domain\Tracking\States;

use Carbon\CarbonImmutable;
use Thunk\Verbs\State;

class QuestionState extends State
{
    public int $user_id;

    public string $label;

    public string $thing;

    public string $unit;

    public int $amount;

    public string $question_type = 'how_long';

    public ?string $guess = null;

    public ?string $active_round_id = null;

    public ?CarbonImmutable $retired_at = null;
}
