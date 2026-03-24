<?php

namespace App\Support\Verbs;

use Glhd\Bits\Bits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;
use Thunk\Verbs\Event;
use Thunk\Verbs\Exceptions\ConcurrencyException;
use Thunk\Verbs\Facades\Id;
use Thunk\Verbs\Lifecycle\EventStore;
use Thunk\Verbs\Lifecycle\MetadataManager;
use Thunk\Verbs\Models\VerbEvent;
use Thunk\Verbs\Models\VerbStateEvent;
use Thunk\Verbs\SingletonState;
use Thunk\Verbs\State;
use Thunk\Verbs\Support\Serializer;

/**
 * Extended EventStore that writes type aliases instead of FQCNs.
 */
class MappedEventStore extends EventStore
{
    public function __construct(
        MetadataManager $metadata,
        protected VerbsTypeMapper $typeMapper,
    ) {
        parent::__construct($metadata);
    }

    /**
     * Format events for database insert with aliases.
     *
     * @param  Event[]  $event_objects
     */
    protected function formatForWrite(array $event_objects): array
    {
        return array_map(fn (Event $event) => [
            'id' => Id::from($event->id),
            'type' => $this->typeMapper->eventClassToAlias($event::class),
            'data' => app(Serializer::class)->serialize($event),
            'metadata' => app(Serializer::class)->serialize($this->metadata->get($event)),
            'created_at' => app(MetadataManager::class)->getEphemeral($event, 'created_at', now()),
            'updated_at' => now(),
        ], $event_objects);
    }

    /**
     * Format state-event relationships for database insert with aliases.
     *
     * @param  Event[]  $event_objects
     */
    protected function formatRelationshipsForWrite(array $event_objects): array
    {
        return collect($event_objects)
            ->flatMap(fn (Event $event) => $event->states()->map(fn ($state) => [
                'id' => snowflake_id(),
                'event_id' => Id::from($event->id),
                'state_id' => Id::from($state->id),
                'state_type' => $this->typeMapper->stateClassToAlias($state::class),
                'created_at' => now(),
                'updated_at' => now(),
            ]))
            ->values()
            ->all();
    }

    /**
     * Read events with alias-based type filtering.
     */
    protected function readEvents(
        ?State $state,
        Bits|UuidInterface|AbstractUid|int|string|null $after_id,
    ): LazyCollection {
        if ($state) {
            return VerbStateEvent::query()
                ->with('event')
                ->unless($state instanceof SingletonState, fn (Builder $query) => $query->where('state_id', $state->id))
                ->where('state_type', $this->typeMapper->stateClassToAlias($state::class))
                ->when($after_id, fn (Builder $query) => $query->whereRelation('event', 'id', '>', Id::from($after_id)))
                ->lazyById()
                ->remember()
                ->map(fn (VerbStateEvent $pivot) => $pivot->event);
        }

        return VerbEvent::query()
            ->when($after_id, fn (Builder $query) => $query->where('id', '>', Id::from($after_id)))
            ->lazyById()
            ->remember();
    }

    /**
     * Guard against concurrent writes using aliases.
     *
     * @param  Event[]  $events
     */
    protected function guardAgainstConcurrentWrites(array $events): void
    {
        $max_event_ids = new Collection;

        $query = VerbStateEvent::query()->toBase();

        $query->select([
            'state_type',
            'state_id',
            DB::raw(sprintf(
                'max(%s) as %s',
                $query->getGrammar()->wrap('event_id'),
                $query->getGrammar()->wrapTable('max_event_id')
            )),
        ]);

        $query->groupBy('state_type', 'state_id');
        $query->orderBy('state_id');

        $query->where(function (BaseBuilder $query) use ($events, $max_event_ids) {
            foreach ($events as $event) {
                foreach ($event->states() as $state) {
                    $stateAlias = $this->typeMapper->stateClassToAlias($state::class);

                    if (! $max_event_ids->has($key = $stateAlias.$state->id)) {
                        $query->orWhere(function (BaseBuilder $query) use ($state, $stateAlias) {
                            $query->where('state_type', $stateAlias);
                            $query->where('state_id', $state->id);
                        });
                        $max_event_ids->put($key, $state->last_event_id);
                    }
                }
            }
        });

        if ($max_event_ids->isEmpty()) {
            return;
        }

        $query->each(function ($result) use ($max_event_ids) {
            $state_type = data_get($result, 'state_type');
            $state_id = data_get($result, 'state_id');
            $max_written_id = (int) data_get($result, 'max_event_id');
            $max_expected_id = $max_event_ids->get($state_type.$state_id, 0);

            if ($max_written_id > $max_expected_id) {
                throw new ConcurrencyException("An event with ID {$max_written_id} has been written to the database for '{$state_type}' with ID {$state_id}. This is higher than the in-memory value of {$max_expected_id}.");
            }
        });
    }
}
