<?php

declare(strict_types=1);

namespace App\Http\Livewire\Datatable;

use Illuminate\Database\Query\Builder;

trait WithSorting
{
    /**
     * The list of fields and direction to be sorted.
     *
     * @var array<string, string>
     */
    private $sorts = [];

    /**
     * Sorts a field by a given key.
     */
    public function sortBy(string $field): ?string
    {
        if (! isset($this->sorts[$field])) {
            return $this->sorts[$field] = 'asc';
        }

        if ($this->sorts[$field] === 'asc') {
            return $this->sorts[$field] = 'desc';
        }

        unset($this->sorts[$field]);
    }

    /**
     * Undocumented function.
     */
    public function applySorting(Builder $query): Builder
    {
        foreach ($this->sorts as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }
}
