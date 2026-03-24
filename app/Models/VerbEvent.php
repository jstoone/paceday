<?php

namespace App\Models;

use App\Support\Verbs\VerbsTypeMapper;
use Thunk\Verbs\Models\VerbEvent as BaseVerbEvent;

/**
 * Extended VerbEvent that handles type alias queries.
 */
class VerbEvent extends BaseVerbEvent
{
    public function scopeType($query, string $type)
    {
        if (str_contains($type, '\\')) {
            $type = app(VerbsTypeMapper::class)->eventClassToAlias($type);
        }

        return $query->where('type', $type);
    }
}
