<?php

namespace App\Domain\Tracking;

enum QuestionType: string
{
    case Duration = 'duration';
    case Frequency = 'frequency';
}
