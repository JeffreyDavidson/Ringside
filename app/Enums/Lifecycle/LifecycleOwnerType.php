<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

enum LifecycleOwnerType: string
{
    case Event = 'event';
    case Manager = 'manager';
    case Match = 'match';
    case Referee = 'referee';
    case Stable = 'stable';
    case TagTeam = 'tag_team';
    case Title = 'title';
    case Venue = 'venue';
    case Wrestler = 'wrestler';

    public static function fromModel(Model $model): self
    {
        return match (true) {
            $model instanceof Event => self::Event,
            $model instanceof Manager => self::Manager,
            $model instanceof EventMatch => self::Match,
            $model instanceof Referee => self::Referee,
            $model instanceof Stable => self::Stable,
            $model instanceof TagTeam => self::TagTeam,
            $model instanceof Title => self::Title,
            $model instanceof Venue => self::Venue,
            $model instanceof Wrestler => self::Wrestler,
            default => throw new InvalidArgumentException('Unsupported lifecycle owner: '.$model::class),
        };
    }

    public function morphAlias(): string
    {
        return $this->value;
    }
}
