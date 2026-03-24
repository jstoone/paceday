<?php

namespace App\Providers;

use App\Support\Verbs\MappedEventStore;
use App\Support\Verbs\MappedSerializer;
use App\Support\Verbs\MappedSnapshotStore;
use App\Support\Verbs\VerbsTypeMapper;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Thunk\Verbs\Contracts\StoresEvents;
use Thunk\Verbs\Contracts\StoresSnapshots;
use Thunk\Verbs\Lifecycle\MetadataManager;
use Thunk\Verbs\Support\Serializer;

/**
 * Registers custom Verbs stores and serializer for type alias support.
 *
 * Stores short aliases like 'tracking.question-asked' instead of
 * full class names like 'App\Domain\Tracking\Events\QuestionAsked'.
 */
class EventSourcingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VerbsTypeMapper::class);

        $this->app->singleton(Serializer::class, function ($app) {
            return new MappedSerializer(
                serializer: $app->make(\Symfony\Component\Serializer\Serializer::class),
                context: $app->make(Repository::class)->get('verbs.serializer_context', []),
                typeMapper: $app->make(VerbsTypeMapper::class),
            );
        });

        $this->app->scoped(MappedEventStore::class, function ($app) {
            return new MappedEventStore(
                $app->make(MetadataManager::class),
                $app->make(VerbsTypeMapper::class),
            );
        });
        $this->app->alias(MappedEventStore::class, StoresEvents::class);

        $this->app->singleton(MappedSnapshotStore::class, function ($app) {
            return new MappedSnapshotStore(
                $app->make(MetadataManager::class),
                $app->make(Serializer::class),
                $app->make(VerbsTypeMapper::class),
            );
        });
        $this->app->alias(MappedSnapshotStore::class, StoresSnapshots::class);
    }
}
