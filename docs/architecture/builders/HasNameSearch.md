# Name Search Builder Concern

`HasNameSearch` provides case-insensitive, word-boundary search for models backed by separate `first_name` and `last_name` columns.

The concern is composed directly by:

- `ManagerBuilder`
- `RefereeBuilder`
- `UserBuilder`

It intentionally is not part of `IndividualBuilder`. Wrestlers are individual roster members, but their schema stores one `name` column, so exposing this query through `WrestlerBuilder` would create an invalid API.

## Query

`whereNameMatches(string $searchTerm)` trims the search term and matches either name column case-insensitively. It accepts exact values and space-delimited name prefixes while avoiding arbitrary substring matches. For example, `John` matches `John` and `John Paul`, but not `Johnny` or `Johnson`.

```php
$managers = Manager::query()
    ->whereNameMatches($searchTerm)
    ->get();
```

The query uses bound parameters for every user-provided value.
