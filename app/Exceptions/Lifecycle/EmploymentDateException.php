<?php

declare(strict_types=1);

namespace App\Exceptions\Lifecycle;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class EmploymentDateException extends BaseBusinessException
{
    public static function currentStartLocked(Manager|Referee|TagTeam|Wrestler $employable): static
    {
        return self::forModel('core.errors.employment_date.current_start_locked', $employable);
    }

    public static function scheduledInThePast(Manager|Referee|TagTeam|Wrestler $employable): static
    {
        return self::forModel('core.errors.employment_date.scheduled_in_past', $employable);
    }
}
