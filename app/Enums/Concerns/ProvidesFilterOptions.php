<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait ProvidesFilterOptions
{
    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return array<string, string> */
    public static function filterOptions(): array
    {
        return ['' => __('core.all'), ...self::options()];
    }
}
