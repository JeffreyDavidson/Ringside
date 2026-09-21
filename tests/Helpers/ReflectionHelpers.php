<?php

declare(strict_types=1);

function reflectionTypeName(ReflectionParameter|ReflectionProperty $reflection): string
{
    $type = $reflection->getType();

    if (! $type instanceof ReflectionNamedType) {
        throw new RuntimeException('Expected a named property or parameter type.');
    }

    return $type->getName();
}

function reflectionReturnTypeName(ReflectionMethod $reflection): string
{
    $type = $reflection->getReturnType();

    if (! $type instanceof ReflectionNamedType) {
        throw new RuntimeException('Expected a named return type.');
    }

    return $type->getName();
}
