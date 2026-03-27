<?php

namespace App\Domain\Tracking\Data;

use App\Domain\Tracking\Period;
use Spatie\LaravelData\Data;

class FrequencyQuestion extends Data
{
    public function __construct(
        public string $thing,
        public Period $period,
    ) {}
}
