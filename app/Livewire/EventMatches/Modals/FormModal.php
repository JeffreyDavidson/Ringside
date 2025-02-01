<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\EventMatches\EventMatchForm;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use App\Traits\Data\PresentsMatchTypesList;
use App\Traits\Data\PresentsRefereesList;
use App\Traits\Data\PresentsTagTeamsList;
use App\Traits\Data\PresentsTitlesList;
use App\Traits\Data\PresentsWrestlersList;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    protected string $modelType = EventMatch::class;

    protected string $modalLanguagePath = 'event-matches';

    protected string $modalFormPath = 'event-matches.modals.form-modal';

    /**
     * String name to render view for each match type.
     */
    public string $subViewToUse;

    public EventMatchForm $modelForm;

    public function fillDummyFields(): void
    {
        /** @var MatchType $matchType */
        $matchType = MatchType::query()->inRandomOrder()->first();

        /** @var Referee $referee */
        $referee = Referee::factory()->create();

        /** @var Title $title */
        $title = Title::factory()->create();

        $this->modelForm->matchTypeId = $matchType->id;
        $this->modelForm->referees = [$referee->id];
        $this->modelForm->titles = [$title->id];
        $this->modelForm->preview = Str::of(fake()->text())->value();
    }
}
