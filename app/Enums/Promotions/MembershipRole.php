<?php

declare(strict_types=1);

namespace App\Enums\Promotions;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';

    /**
     * @var list<string>
     */
    private const array CONTENT_ABILITIES = [
        'view',
        'viewAny',
        'create',
        'update',
        'delete',
        'restore',
        'employ',
        'release',
        'retire',
        'unretire',
        'suspend',
        'reinstate',
        'injure',
        'clearFromInjury',
        'establish',
        'disband',
        'debut',
        'pull',
        'activate',
        'deactivate',
    ];

    public function allows(string $ability): bool
    {
        return match ($this) {
            self::Owner => in_array($ability, self::CONTENT_ABILITIES, true)
                || $ability === 'manageMembers',
            self::Manager => in_array($ability, self::CONTENT_ABILITIES, true),
            self::Member => in_array($ability, ['view', 'viewAny'], true),
        };
    }
}
