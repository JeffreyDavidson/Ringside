<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/**
 * Orders a collection that is iterated before its rows are locked by ascending primary key.
 *
 * The key is qualified with the model's own table, so the order stays on the related model's id when the query is a
 * many-to-many relationship joined to its pivot table.
 */
trait OrdersByKeyForLocking
{
    public function inLockOrder(): static
    {
        $this->orderBy($this->getModel()->getQualifiedKeyName());

        return $this;
    }
}
