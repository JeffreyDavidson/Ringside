<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

enum LifecycleOwnerType: string
{
    case Event = 'event';
    case Manager = 'manager';
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
        return match ($this) {
            self::Event => (new Event())->getMorphClass(),
            self::Manager => (new Manager())->getMorphClass(),
            self::Referee => (new Referee())->getMorphClass(),
            self::Stable => (new Stable())->getMorphClass(),
            self::TagTeam => (new TagTeam())->getMorphClass(),
            self::Title => (new Title())->getMorphClass(),
            self::Venue => (new Venue())->getMorphClass(),
            self::Wrestler => (new Wrestler())->getMorphClass(),
        };
    }
}
