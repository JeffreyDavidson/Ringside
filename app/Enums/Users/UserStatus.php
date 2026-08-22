<?php

declare(strict_types=1);

namespace App\Enums\Users;

enum UserStatus: string
{
    case Unverified = 'unverified';
    case Active = 'active';
    case Inactive = 'inactive';

    public function color(): string
    {
        return match ($this) {
            self::Unverified => 'warning',
            self::Active => 'success',
            self::Inactive => 'light',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    /** @return array<string, string> */
    public static function filterOptions(): array
    {
        /** @var array<string, string> $statusOptions */
        $statusOptions = array_combine(
            array_map(static fn (self $status): string => $status->value, self::cases()),
            array_map(static fn (self $status): string => $status->label(), self::cases()),
        );

        return [
            '' => __('core.all'),
            ...$statusOptions,
        ];
    }
}
