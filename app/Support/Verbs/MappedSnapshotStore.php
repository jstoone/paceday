<?php

namespace App\Support\Verbs;

use Glhd\Bits\Bits;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;
use Thunk\Verbs\Exceptions\StateIsNotSingletonException;
use Thunk\Verbs\Facades\Id;
use Thunk\Verbs\Lifecycle\MetadataManager;
use Thunk\Verbs\Lifecycle\SnapshotStore;
use Thunk\Verbs\Models\VerbSnapshot;
use Thunk\Verbs\State;
use Thunk\Verbs\Support\Serializer;
use Thunk\Verbs\Support\StateCollection;

/**
 * Extended SnapshotStore that writes type aliases instead of FQCNs.
 */
class MappedSnapshotStore extends SnapshotStore
{
    public function __construct(
        MetadataManager $metadata,
        Serializer $serializer,
        protected VerbsTypeMapper $typeMapper,
    ) {
        parent::__construct($metadata, $serializer);
    }

    /**
     * Format a state for database insert with alias.
     */
    protected function formatForWrite(State $state): array
    {
        return [
            'id' => $this->metadata->getEphemeral($state, 'snapshot_id', snowflake_id()),
            'state_id' => Id::from($state->id),
            'type' => $this->typeMapper->stateClassToAlias($state::class),
            'data' => $this->serializer->serialize($state),
            'last_event_id' => Id::tryFrom($state->last_event_id),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Load a single state by ID and type using alias.
     */
    protected function loadOne(Bits|UuidInterface|AbstractUid|int|string $id, string $type): ?State
    {
        $snapshots = VerbSnapshot::query()
            ->where('type', '=', $this->typeMapper->stateClassToAlias($type))
            ->where('state_id', $id)
            ->take(2)
            ->get();

        $count = $snapshots->count();

        return match ($count) {
            0 => null,
            1 => $snapshots->first()->state(),
            default => throw new MultipleRecordsFoundException($count),
        };
    }

    /**
     * Load multiple states by IDs and type using alias.
     */
    protected function loadMany(Collection $ids, string $type): StateCollection
    {
        $ids->ensure([Bits::class, UuidInterface::class, AbstractUid::class, 'int', 'string']);

        $states = VerbSnapshot::query()
            ->where('type', '=', $this->typeMapper->stateClassToAlias($type))
            ->whereIn('state_id', $ids)
            ->get()
            ->map(fn (VerbSnapshot $snapshot) => $snapshot->state());

        return StateCollection::make($states);
    }

    /**
     * Load a singleton state using alias.
     */
    public function loadSingleton(string $type): ?State
    {
        $snapshots = VerbSnapshot::query()
            ->where('type', $this->typeMapper->stateClassToAlias($type))
            ->limit(2)
            ->get();

        if ($snapshots->count() > 1) {
            throw new StateIsNotSingletonException($type);
        }

        return $snapshots->first()?->state();
    }
}
