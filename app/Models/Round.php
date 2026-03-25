<?php

namespace App\Models;

use Database\Factories\RoundFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Round extends Model
{
    /** @use HasFactory<RoundFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
