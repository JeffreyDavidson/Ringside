<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * A search term cleaned for a "contains" LIKE query.
 *
 * The LIKE wildcards (% and _) and the backslash are dropped rather than escaped, so no database-specific escaping is
 * needed and a person cannot widen the search with them. The rest is trimmed and may be cut to a maximum length.
 */
final readonly class LikeTerm
{
    private function __construct(private string $needle) {}

    /**
     * @param  int|null  $maxLength  Characters kept after the term is cleaned and trimmed, or null to keep them all
     */
    public static function from(string $term, ?int $maxLength = null): self
    {
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $term));

        return new self($maxLength === null ? $needle : mb_substr($needle, 0, $maxLength));
    }

    /**
     * Whether nothing is left to search for, so the query should not be filtered at all.
     */
    public function isEmpty(): bool
    {
        return $this->needle === '';
    }

    /**
     * The pattern for a LIKE value: the cleaned term between wildcards.
     */
    public function contains(): string
    {
        return "%{$this->needle}%";
    }
}
