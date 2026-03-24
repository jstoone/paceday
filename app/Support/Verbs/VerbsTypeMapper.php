<?php

namespace App\Support\Verbs;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Handles deterministic FQCN↔alias conversion for Verbs Events and States.
 *
 * Convention:
 *   Events: App\Domain\{Context}\Events\{Name} → {context}.{name}
 *   States: App\Domain\{Context}\States\{Name}State → {context}.{name}
 */
class VerbsTypeMapper
{
    /**
     * Known uppercase contexts (acronyms).
     *
     * If a domain context is an acronym (e.g., API), add it here
     * so alias→FQCN conversion restores the correct casing.
     *
     * @var string[]
     */
    protected const UPPERCASE_CONTEXTS = [];

    /**
     * Convert an Event FQCN to its alias.
     */
    public function eventClassToAlias(string $fqcn): string
    {
        return $this->fqcnToAlias($fqcn, isState: false);
    }

    /**
     * Convert an alias to its Event FQCN.
     */
    public function eventAliasToClass(string $alias): string
    {
        return $this->aliasToFqcn($alias, isState: false);
    }

    /**
     * Convert a State FQCN to its alias.
     */
    public function stateClassToAlias(string $fqcn): string
    {
        return $this->fqcnToAlias($fqcn, isState: true);
    }

    /**
     * Convert an alias to its State FQCN.
     */
    public function stateAliasToClass(string $alias): string
    {
        return $this->aliasToFqcn($alias, isState: true);
    }

    /**
     * Convert a FQCN to alias using naming convention.
     *
     * @throws InvalidArgumentException If class doesn't follow convention
     */
    public function fqcnToAlias(string $fqcn, bool $isState): string
    {
        // Match: App\Domain\{Context}\(Events|States)\{Name}
        if (! preg_match('/^App\\\\Domain\\\\(\w+)\\\\(?:Events|States)\\\\(.+)$/', $fqcn, $matches)) {
            throw new InvalidArgumentException(
                "Class {$fqcn} must be in App\\Domain\\{Context}\\Events or App\\Domain\\{Context}\\States namespace"
            );
        }

        $context = $matches[1];
        $name = $matches[2];

        // Strip "State" suffix for states
        if ($isState && str_ends_with($name, 'State')) {
            $name = substr($name, 0, -5);
        }

        // Use lowercase for context (handles acronyms correctly)
        // Use kebab-case for class name (handles multi-word names)
        return strtolower($context).'.'.Str::kebab($name);
    }

    /**
     * Convert an alias to FQCN using naming convention.
     */
    public function aliasToFqcn(string $alias, bool $isState): string
    {
        if (! str_contains($alias, '.')) {
            throw new InvalidArgumentException("Invalid alias format: {$alias}. Expected format: context.name");
        }

        [$context, $name] = explode('.', $alias, 2);

        // Handle known uppercase contexts (acronyms)
        $contextStudly = in_array(strtolower($context), self::UPPERCASE_CONTEXTS, true)
            ? strtoupper($context)
            : Str::studly($context);

        $nameStudly = Str::studly($name);

        $namespace = $isState ? 'States' : 'Events';
        $suffix = $isState ? 'State' : '';

        return "App\\Domain\\{$contextStudly}\\{$namespace}\\{$nameStudly}{$suffix}";
    }

    /**
     * Check if a string looks like an alias (has dot, no backslash).
     */
    public function isAlias(string $value): bool
    {
        return str_contains($value, '.') && ! str_contains($value, '\\');
    }

    /**
     * Check if a string looks like a FQCN (has backslash).
     */
    public function isFqcn(string $value): bool
    {
        return str_contains($value, '\\');
    }
}
