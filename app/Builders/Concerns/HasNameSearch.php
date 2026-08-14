<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/**
 * Provides name search for models with first_name and last_name columns.
 */
trait HasNameSearch
{
    /**
     * Scope a query to search for records matching the given name search term.
     *
     * Searches for exact matches or word-boundary prefix matches on first_name and last_name.
     * For example, searching "John" will match "John Smith" but not "Johnson".
     *
     * @param  string  $searchTerm  The term to search for
     */
    public function whereNameMatches(string $searchTerm): static
    {
        $trimmedTerm = mb_trim($searchTerm);

        return $this->where(function ($query) use ($trimmedTerm) {
            $query->whereRaw('LOWER(first_name) = LOWER(?)', [$trimmedTerm])
                ->orWhereRaw('LOWER(last_name) = LOWER(?)', [$trimmedTerm])
                ->orWhereRaw('LOWER(first_name) LIKE LOWER(?)', [$trimmedTerm.' %'])
                ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', [$trimmedTerm.' %']);
        });
    }
}
