<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\RoundEndAdjusted;
use App\Domain\Tracking\States\RoundState;
use Carbon\CarbonImmutable;
use Thunk\Verbs\Facades\Verbs;

class AdjustRoundEnd
{
    public function execute(
        string $round_id,
        CarbonImmutable $new_ended_at,
    ): RoundEndAdjusted {
        $round = RoundState::load($round_id);

        $event = verb(new RoundEndAdjusted(
            round_id: $round_id,
            old_ended_at: $round->ended_at,
            new_ended_at: $new_ended_at,
        ));

        Verbs::commit();

        return $event;
    }
}
