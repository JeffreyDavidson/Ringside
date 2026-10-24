<?php

declare(strict_types=1);

namespace App\Lifecycle\Naming;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * The check behind a guarded value (a name, a signature move) that must stay unique within a scope: refuse it when
 * another record already has it. It never writes and never locks; the Create and Update actions take the
 * {@see RecordNameLock} (or the stable name lock) first and then ask this, so the lookup sees the other transaction's
 * committed record.
 *
 * What the scope is stays with the caller, because it differs per record type: the query it passes decides whether
 * deleted records count, which promotion's records are looked at (a null promotion matches only records without one)
 * and whether the promotion context scope applies. The value is compared the way the column's collation compares it.
 */
final readonly class GuardedNameRequirements
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $records  The records the value must be free among, already narrowed to the scope
     * @param  Closure(): Throwable  $taken  Builds the exception thrown when another record has the value
     * @param  Model|null  $except  The record being updated, which may keep its own value
     *
     * @throws Throwable The exception $taken builds, when another record in the scope has the value
     */
    public function ensureAvailable(Builder $records, string $column, string $value, Closure $taken, ?Model $except = null): void
    {
        $query = (clone $records)->where($column, $value);

        if ($except instanceof Model) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw $taken();
        }
    }
}
