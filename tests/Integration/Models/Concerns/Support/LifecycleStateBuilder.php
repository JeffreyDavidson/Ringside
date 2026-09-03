<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
final class LifecycleStateBuilder extends Builder
{
    /** @param TModel $model */
    public function __construct(QueryBuilder $query, Model $model)
    {
        parent::__construct($query);

        $this->setModel($model);
    }
}
