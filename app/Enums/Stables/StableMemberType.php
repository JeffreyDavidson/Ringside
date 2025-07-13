<?php

declare(strict_types=1);

namespace App\Enums\Stables;

use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Enumeration for stable member types.
 *
 * Defines the valid types of members that can belong to a stable and their
 * corresponding relationship names. Provides type-safe member type handling
 * and automatic model type detection.
 *
 * @example
 * ```php
 * $type = StableMemberType::fromModel($wrestler);
 * $relationshipName = $type->getRelationshipName(); // 'wrestlers'
 * ```
 */
enum StableMemberType: string
{
    case WRESTLER = 'wrestlers';
    case TAG_TEAM = 'tagTeams';
    case MANAGER = 'managers';

    /**
     * Create enum instance from a model.
     *
     * Automatically detects the member type based on the model class
     * and returns the appropriate enum value.
     *
     * @param  Model  $model  The model to detect type for
     * @return self The corresponding enum value
     *
     * @throws InvalidArgumentException If the model type is not supported
     */
    public static function fromModel(Model $model): self
    {
        return match (get_class($model)) {
            Wrestler::class => self::WRESTLER,
            TagTeam::class => self::TAG_TEAM,
            Manager::class => self::MANAGER,
            default => throw new InvalidArgumentException(
                'Unsupported member type: '.get_class($model)
            )
        };
    }

    /**
     * Get the relationship name for this member type.
     *
     * Returns the relationship name used in the Stable model for this
     * type of member (e.g., 'wrestlers', 'tagTeams', 'managers').
     *
     * @return string The relationship name
     */
    public function getRelationshipName(): string
    {
        return $this->value;
    }

    /**
     * Get a human-readable label for this member type.
     *
     * @return string The display label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WRESTLER => 'Wrestler',
            self::TAG_TEAM => 'Tag Team',
            self::MANAGER => 'Manager',
        };
    }

    /**
     * Get the current relationship method name for this member type.
     *
     * Returns the method name used to get current members of this type
     * from a stable (e.g., 'currentWrestlers').
     *
     * @return string The current relationship method name
     */
    public function getCurrentRelationshipName(): string
    {
        return 'current'.ucfirst($this->value);
    }
}
