<?php

namespace App\ViewModels;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Spatie\ViewModels\ViewModel;

class TagTeamViewModel extends ViewModel
{
    /** @var $tagTeam */
    public $tagTeam;

    /** @var $tagTeam */
    public $wrestlers;

    public $tagTeamPartner1;
    public $tagTeamPartner2;

    /**
     * Create a new tagTeam view model instance.
     *
     * @param App\Models\TagTeam|null $tagTeam
     */
    public function __construct(TagTeam $tagTeam = null)
    {
        $this->tagTeam = $tagTeam ?? new TagTeam;
        $this->tagTeam->started_at = optional($this->tagTeam->started_at)->toDateTimeString();
        $this->wrestlers = Wrestler::employed()->orWhere->pendingEmployment()->pluck('name', 'id');
        $this->tagTeamPartner1 = old('wrestler1', optional($this->tagTeam->currentWrestlers->first())->id);
        $this->tagTeamPartner2 = old('wrestler2', optional($this->tagTeam->currentWrestlers->last())->id);
    }

    /**
     * Determine if the given option is the current selected option.
     *
     * @param  string  $option
     * @return bool
     */
    public function isTagTeamPartner1($option)
    {
        return $option === $this->tagTeamPartner1;
    }

    /**
     * Determine if the given option is the current selected option.
     *
     * @param  string  $option
     * @return bool
     */
    public function isTagTeamPartner2($option)
    {
        return $option === $this->tagTeamPartner2;
    }
}
