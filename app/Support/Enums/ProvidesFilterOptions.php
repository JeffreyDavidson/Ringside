<?php

declare(strict_types=1);

namespace App\Support\Enums;

trait ProvidesFilterOptions
{
    /** @return array<string, string> */
    public static function filterOptions(): array
    {
        $options = ['' => __('core.all')];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
