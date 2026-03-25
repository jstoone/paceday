<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\RoundStartAdjusted;
use App\Domain\Tracking\States\RoundState;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

class AdjustRoundStart
{
    public function execute(
        string $round_id,
        CarbonImmutable $new_occurred_at,
    ): RoundStartAdjusted {
        $round = RoundState::load($round_id);

        $event = verb(new RoundStartAdjusted(
            round_id: $round_id,
            old_occurred_at: $round->occurred_at,
            new_occurred_at: $new_occurred_at,
        ));

        Verbs::commit();

        return $event;
    }
}
