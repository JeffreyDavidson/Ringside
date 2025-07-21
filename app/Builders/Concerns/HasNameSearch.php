<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/**
 * Provides name-based search functionality for builders of models with first_name and last_name columns.
 * 
 * This trait adds query scopes for intelligent name searching that handles:
 * - Case-insensitive exact matching
 * - Word boundary prefix matching to prevent false positives
 * - Proper SQL injection protection with parameter binding
 * 
 * Used by UserBuilder, ManagerBuilder, RefereeBuilder, etc.
 */
trait HasNameSearch
{
    /**
     * Scope a query to search for records matching the given name search term.
     * 
     * Searches for exact matches or word-boundary prefix matches on first_name and last_name.
     * For example, searching "John" will match "John Smith" but not "Johnson".
     * 
     * @param string $searchTerm The term to search for
     * @return static
     */
    public function whereNameMatches(string $searchTerm): static
    {
        $trimmedTerm = trim($searchTerm);
        
        return $this->where(function($query) use ($trimmedTerm) {
            $query->whereRaw('LOWER(first_name) = LOWER(?)', [$trimmedTerm])
                  ->orWhereRaw('LOWER(last_name) = LOWER(?)', [$trimmedTerm])
                  ->orWhereRaw('LOWER(first_name) LIKE LOWER(?)', [$trimmedTerm . ' %'])
                  ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', [$trimmedTerm . ' %']);
        });
    }

    /**
     * Scope a query to search for records where names contain the search term.
     * 
     * Uses broader LIKE matching for more flexible search results.
     * 
     * @param string $searchTerm The term to search for
     * @return static
     */
    public function whereNameContains(string $searchTerm): static
    {
        $trimmedTerm = trim($searchTerm);
        
        return $this->where(function($query) use ($trimmedTerm) {
            $query->whereRaw('LOWER(first_name) LIKE LOWER(?)', ['%' . $trimmedTerm . '%'])
                  ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', ['%' . $trimmedTerm . '%']);
        });
    }
}