<?php

declare(strict_types=1);

namespace App\Enums\Shared;

/**
 * Value of an index table filter that lists only soft-deleted rows.
 */
enum DeletedFilter: string
{
    case Deleted = 'deleted';

    public function label(): string
    {
        return __('core.deleted');
    }

    /**
     * The status filter options with the Deleted option appended.
     *
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    public static function appendTo(array $options): array
    {
        return [...$options, self::Deleted->value => self::Deleted->label()];
    }
}
