<?php

declare(strict_types=1);

namespace App\Http\Livewire\Datatable;

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
     *
     * @param  string  $field
     * @return string|null
     */
    public function sortBy($field)
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
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return  \Illuminate\Database\Query\Builder
     */
    public function applySorting($query)
    {
        foreach ($this->sorts as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }
}
