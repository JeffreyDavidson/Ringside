<?php

declare(strict_types=1);

namespace App\Enums\Roster;

enum RosterLifecycleAction: string
{
    case Employ = 'employ';
    case ClearFromInjury = 'clear_from_injury';
    case Injure = 'injure';
    case Reinstate = 'reinstate';
    case Release = 'release';
    case Restore = 'restore';
    case Retire = 'retire';
    case Suspend = 'suspend';
    case Unretire = 'unretire';

    public function ability(): string
    {
        return match ($this) {
            self::ClearFromInjury => 'clearFromInjury',
            default => $this->value,
        };
    }

    public function usesTrashedModel(): bool
    {
        return $this === self::Restore;
    }

    /**
     * @return list<RosterEntityType>
     */
    public function supportedEntityTypes(): array
    {
        return match ($this) {
            self::ClearFromInjury, self::Injure => [
                RosterEntityType::Wrestler,
                RosterEntityType::Manager,
                RosterEntityType::Referee,
            ],
            default => RosterEntityType::cases(),
        };
    }

    public function supports(RosterEntityType $entityType): bool
    {
        return in_array($entityType, $this->supportedEntityTypes(), true);
    }

    public function successAction(): string
    {
        return match ($this) {
            self::Employ => 'employed',
            self::ClearFromInjury => 'cleared_from_injury',
            self::Injure => 'injured',
            self::Reinstate => 'reinstated',
            self::Release => 'released',
            self::Restore => 'restored',
            self::Retire => 'retired',
            self::Suspend => 'suspended',
            self::Unretire => 'unretired',
        };
    }
}
