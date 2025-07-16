<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Model structure-specific custom expectations.
 *
 * These expectations provide convenient methods for testing model
 * structure, relationships, traits, and interfaces.
 */

// Trait and Interface Validation
expect()->extend('toUseTrait', function (string $trait) {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toUseTrait() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;
    $traits = class_uses_recursive($class);

    return in_array($trait, $traits);
});

expect()->extend('toImplementInterface', function (string $interface) {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toImplementInterface() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;
    $interfaces = class_implements($class);

    return in_array($interface, $interfaces);
});

expect()->extend('toExtendBaseModel', function (string $baseModel) {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toExtendBaseModel() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;

    return is_subclass_of($class, $baseModel);
});

// Relationship Validation
expect()->extend('toHaveRelationship', function (string $relationshipName, string $relationshipType) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveRelationship() can only be used on Eloquent models');
    }

    if (! method_exists($this->value, $relationshipName)) {
        return false;
    }

    $relationship = $this->value->$relationshipName();

    return $relationship instanceof $relationshipType;
});

expect()->extend('toHaveBelongsToRelationship', function (string $relationshipName, ?string $relatedModel = null) {
    if (! $this->toHaveRelationship($relationshipName, BelongsTo::class)) {
        return false;
    }

    if ($relatedModel) {
        $relationship = $this->value->$relationshipName();

        return $relationship->getRelated() instanceof $relatedModel;
    }

    return true;
});

expect()->extend('toHaveHasManyRelationship', function (string $relationshipName, ?string $relatedModel = null) {
    if (! $this->toHaveRelationship($relationshipName, HasMany::class)) {
        return false;
    }

    if ($relatedModel) {
        $relationship = $this->value->$relationshipName();

        return $relationship->getRelated() instanceof $relatedModel;
    }

    return true;
});

expect()->extend('toHaveHasOneRelationship', function (string $relationshipName, ?string $relatedModel = null) {
    if (! $this->toHaveRelationship($relationshipName, HasOne::class)) {
        return false;
    }

    if ($relatedModel) {
        $relationship = $this->value->$relationshipName();

        return $relationship->getRelated() instanceof $relatedModel;
    }

    return true;
});

expect()->extend('toHaveBelongsToManyRelationship', function (string $relationshipName, ?string $relatedModel = null) {
    if (! $this->toHaveRelationship($relationshipName, BelongsToMany::class)) {
        return false;
    }

    if ($relatedModel) {
        $relationship = $this->value->$relationshipName();

        return $relationship->getRelated() instanceof $relatedModel;
    }

    return true;
});

expect()->extend('toHavePolymorphicRelationship', function (string $relationshipName, string $relationshipType) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHavePolymorphicRelationship() can only be used on Eloquent models');
    }

    if (! method_exists($this->value, $relationshipName)) {
        return false;
    }

    $relationship = $this->value->$relationshipName();

    $validTypes = [MorphOne::class, MorphMany::class, MorphToMany::class];

    return in_array($relationshipType, $validTypes) && $relationship instanceof $relationshipType;
});

// Foreign Key Validation
expect()->extend('toHaveCorrectForeignKey', function (string $relationshipName, string $expectedForeignKey) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCorrectForeignKey() can only be used on Eloquent models');
    }

    if (! method_exists($this->value, $relationshipName)) {
        return false;
    }

    $relationship = $this->value->$relationshipName();

    if (! method_exists($relationship, 'getForeignKeyName')) {
        return false;
    }

    return $relationship->getForeignKeyName() === $expectedForeignKey;
});

// Model Configuration Validation
expect()->extend('toHaveCorrectTable', function (string $expectedTable) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCorrectTable() can only be used on Eloquent models');
    }

    return $this->value->getTable() === $expectedTable;
});

expect()->extend('toHaveCorrectFillable', function (array $expectedFillable) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCorrectFillable() can only be used on Eloquent models');
    }

    return $this->value->getFillable() === $expectedFillable;
});

expect()->extend('toHaveCorrectCasts', function (array $expectedCasts) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCorrectCasts() can only be used on Eloquent models');
    }

    $casts = $this->value->getCasts();

    foreach ($expectedCasts as $field => $cast) {
        if (! isset($casts[$field]) || $casts[$field] !== $cast) {
            return false;
        }
    }

    return true;
});

expect()->extend('toHaveCorrectGuarded', function (array $expectedGuarded) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCorrectGuarded() can only be used on Eloquent models');
    }

    return $this->value->getGuarded() === $expectedGuarded;
});

// Builder Validation
expect()->extend('toHaveCustomBuilder', function (string $builderClass) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveCustomBuilder() can only be used on Eloquent models');
    }

    $builder = $this->value->query();

    return $builder instanceof $builderClass;
});

expect()->extend('toHaveDefaultBuilder', function () {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveDefaultBuilder() can only be used on Eloquent models');
    }

    $builder = $this->value->query();

    return $builder instanceof Illuminate\Database\Eloquent\Builder;
});

// Attribute Validation
expect()->extend('toHaveAttribute', function (string $attribute) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveAttribute() can only be used on Eloquent models');
    }

    return $this->value->hasAttribute($attribute);
});

expect()->extend('toHaveAttributeValue', function (string $attribute, $expectedValue) {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveAttributeValue() can only be used on Eloquent models');
    }

    return $this->value->getAttribute($attribute) === $expectedValue;
});

// Scope Validation
expect()->extend('toHaveScope', function (string $scopeName) {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toHaveScope() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;
    $scopeMethodName = 'scope'.ucfirst($scopeName);

    return method_exists($class, $scopeMethodName);
});

// Event Validation
expect()->extend('toHaveModelEvent', function (string $eventName) {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toHaveModelEvent() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;

    // Check if the model has the event method or observer
    $eventMethods = ['boot', 'bootTraits', 'initializeTraits'];

    foreach ($eventMethods as $method) {
        if (method_exists($class, $method)) {
            return true;
        }
    }

    return false;
});

// Factory Validation
expect()->extend('toHaveFactory', function () {
    if (! is_string($this->value) && ! is_object($this->value)) {
        throw new InvalidArgumentException('toHaveFactory() expects a class name string or object');
    }

    $class = is_object($this->value) ? get_class($this->value) : $this->value;

    return in_array(Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses_recursive($class));
});

expect()->extend('toHaveWorkingFactory', function () {
    if (! is_object($this->value) || ! $this->value instanceof Model) {
        throw new InvalidArgumentException('toHaveWorkingFactory() can only be used on Eloquent models');
    }

    try {
        $model = $this->value->factory()->make();

        return $model instanceof Model;
    } catch (Exception $e) {
        return false;
    }
});
