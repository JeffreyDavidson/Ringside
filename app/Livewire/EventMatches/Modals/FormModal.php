<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\EventMatches\EventMatchForm;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsRefereesList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use Illuminate\Support\Str;

/**
 * @extends BaseModal<EventMatchForm, EventMatch>
 */
class FormModal extends BaseModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    /**
     * String name to render view for each match type.
     */
    public string $subViewToUse;

    protected string $modalFormPath = 'event-matches.modals.form-modal';

    protected $modelForm;

    protected $modelType;

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
