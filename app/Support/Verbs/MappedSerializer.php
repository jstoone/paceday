<?php

namespace App\Support\Verbs;

use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Thunk\Verbs\Support\Serializer;

/**
 * Extended Serializer that converts type aliases back to FQCNs on deserialize.
 */
class MappedSerializer extends Serializer
{
    public function __construct(
        SymfonySerializer $serializer,
        array $context,
        protected VerbsTypeMapper $typeMapper,
    ) {
        parent::__construct($serializer, $context);
    }

    public function deserialize(
        object|string $target,
        string|array $data,
        bool $call_constructor = false,
    ): mixed {
        if (is_string($target) && $this->typeMapper->isAlias($target)) {
            $target = $this->resolveAliasToClass($target);
        }

        return parent::deserialize($target, $data, $call_constructor);
    }

    /**
     * Resolve an alias to its FQCN, trying event first then state.
     */
    protected function resolveAliasToClass(string $alias): string
    {
        $eventClass = $this->typeMapper->aliasToFqcn($alias, isState: false);

        if (class_exists($eventClass)) {
            return $eventClass;
        }

        return $this->typeMapper->aliasToFqcn($alias, isState: true);
    }
}
