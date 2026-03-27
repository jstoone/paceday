<?php

namespace App\Domain\Tracking\Data;

use Spatie\LaravelData\Data;

class DurationQuestion extends Data
{
    public function __construct(
        public string $thing,
        public string $unit,
        public int $amount,
    ) {}
}
