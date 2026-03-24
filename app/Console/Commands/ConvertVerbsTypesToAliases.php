<?php

namespace App\Console\Commands;

use App\Support\Verbs\VerbsTypeMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertVerbsTypesToAliases extends Command
{
    protected $signature = 'verbs:convert-types-to-aliases
                            {--dry-run : Show what would be converted without making changes}
                            {--reverse : Convert aliases back to FQCNs}';

    protected $description = 'Convert Verbs type columns from FQCNs to short aliases (or reverse)';

    public function handle(VerbsTypeMapper $mapper): int
    {
        $dryRun = $this->option('dry-run');
        $reverse = $this->option('reverse');

        if ($dryRun) {
            $this->info('Dry run mode - no changes will be made');
        }

        $direction = $reverse ? 'aliases → FQCNs' : 'FQCNs → aliases';
        $this->info("Converting {$direction}...\n");

        $this->convertEventTypes($mapper, $dryRun, $reverse);
        $this->convertSnapshotTypes($mapper, $dryRun, $reverse);
        $this->convertStateEventTypes($mapper, $dryRun, $reverse);

        if (! $dryRun) {
            $this->newLine();
            $this->info('Conversion complete!');
        }

        return Command::SUCCESS;
    }

    protected function convertEventTypes(VerbsTypeMapper $mapper, bool $dryRun, bool $reverse): void
    {
        $this->info('verb_events.type');

        $events = DB::table('verb_events')
            ->select('type')
            ->distinct()
            ->pluck('type');

        $converted = 0;

        foreach ($events as $type) {
            $shouldConvert = $reverse
                ? $mapper->isAlias($type)
                : $mapper->isFqcn($type);

            if (! $shouldConvert) {
                continue;
            }

            try {
                $newType = $reverse
                    ? $mapper->eventAliasToClass($type)
                    : $mapper->eventClassToAlias($type);

                $count = DB::table('verb_events')->where('type', $type)->count();

                if ($dryRun) {
                    $this->line("   Would convert: {$type} → {$newType} ({$count} records)");
                } else {
                    DB::table('verb_events')
                        ->where('type', $type)
                        ->update(['type' => $newType]);

                    $this->line("   Converted: {$type} → {$newType} ({$count} records)");
                }

                $converted++;
            } catch (\Exception $e) {
                $this->warn("   Skipped {$type}: {$e->getMessage()}");
            }
        }

        if ($converted === 0) {
            $this->line('   No conversions needed');
        }
    }

    protected function convertSnapshotTypes(VerbsTypeMapper $mapper, bool $dryRun, bool $reverse): void
    {
        $this->newLine();
        $this->info('verb_snapshots.type');

        $types = DB::table('verb_snapshots')
            ->select('type')
            ->distinct()
            ->pluck('type');

        $converted = 0;

        foreach ($types as $type) {
            $shouldConvert = $reverse
                ? $mapper->isAlias($type)
                : $mapper->isFqcn($type);

            if (! $shouldConvert) {
                continue;
            }

            try {
                $newType = $reverse
                    ? $mapper->stateAliasToClass($type)
                    : $mapper->stateClassToAlias($type);

                $count = DB::table('verb_snapshots')->where('type', $type)->count();

                if ($dryRun) {
                    $this->line("   Would convert: {$type} → {$newType} ({$count} records)");
                } else {
                    DB::table('verb_snapshots')
                        ->where('type', $type)
                        ->update(['type' => $newType]);

                    $this->line("   Converted: {$type} → {$newType} ({$count} records)");
                }

                $converted++;
            } catch (\Exception $e) {
                $this->warn("   Skipped {$type}: {$e->getMessage()}");
            }
        }

        if ($converted === 0) {
            $this->line('   No conversions needed');
        }
    }

    protected function convertStateEventTypes(VerbsTypeMapper $mapper, bool $dryRun, bool $reverse): void
    {
        $this->newLine();
        $this->info('verb_state_events.state_type');

        $types = DB::table('verb_state_events')
            ->select('state_type')
            ->distinct()
            ->pluck('state_type');

        $converted = 0;

        foreach ($types as $type) {
            $shouldConvert = $reverse
                ? $mapper->isAlias($type)
                : $mapper->isFqcn($type);

            if (! $shouldConvert) {
                continue;
            }

            try {
                $newType = $reverse
                    ? $mapper->stateAliasToClass($type)
                    : $mapper->stateClassToAlias($type);

                $count = DB::table('verb_state_events')->where('state_type', $type)->count();

                if ($dryRun) {
                    $this->line("   Would convert: {$type} → {$newType} ({$count} records)");
                } else {
                    DB::table('verb_state_events')
                        ->where('state_type', $type)
                        ->update(['state_type' => $newType]);

                    $this->line("   Converted: {$type} → {$newType} ({$count} records)");
                }

                $converted++;
            } catch (\Exception $e) {
                $this->warn("   Skipped {$type}: {$e->getMessage()}");
            }
        }

        if ($converted === 0) {
            $this->line('   No conversions needed');
        }
    }
}
