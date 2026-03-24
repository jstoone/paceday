<?php

namespace App\Models;

use App\Support\Verbs\VerbsTypeMapper;
use Thunk\Verbs\Models\VerbStateEvent as BaseVerbStateEvent;
use Thunk\Verbs\State;

/**
 * Extended VerbStateEvent that resolves state type aliases.
 */
class VerbStateEvent extends BaseVerbStateEvent
{
    public function state(): State
    {
        $stateClass = $this->resolveStateType($this->state_type);

        return $stateClass::load($this->state_id);
    }

    protected function resolveStateType(string $type): string
    {
        if (str_contains($type, '\\')) {
            return $type;
        }

        return app(VerbsTypeMapper::class)->stateAliasToClass($type);
    }
}
