<?php

namespace App\Domain\Tracking\States;

use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Data\FrequencyQuestion;
use App\Domain\Tracking\QuestionType;
use Carbon\CarbonImmutable;
use Thunk\Verbs\State;

class QuestionState extends State
{
    public int $user_id;

    public string $label;

    public QuestionType $question_type = QuestionType::Duration;

    public DurationQuestion|FrequencyQuestion|null $question = null;

    public ?string $guess = null;

    public ?string $active_round_id = null;

    public ?CarbonImmutable $retired_at = null;
}
