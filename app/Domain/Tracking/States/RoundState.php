<?php

namespace App\Domain\Tracking\States;

use Carbon\CarbonImmutable;
use Thunk\Verbs\State;

class RoundState extends State
{
    public string $question_id;

    public string $status = 'active';

    public CarbonImmutable $occurred_at;

    public ?CarbonImmutable $ended_at = null;

    public ?CarbonImmutable $recorded_at = null;

    public ?CarbonImmutable $voided_at = null;
}
