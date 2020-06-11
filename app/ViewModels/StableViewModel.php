<?php

namespace App\ViewModels;

use App\Models\Stable;
use Spatie\ViewModels\ViewModel;

class StableViewModel extends ViewModel
{
    /** @var $stable */
    public $stable;

    /**
     * Create a new stable view model instance.
     *
     * @param App\Models\Stable|null $stable
     */
    public function __construct(Stable $stable = null)
    {
        $this->stable = $stable ?? new Stable;
        $this->stable->started_at = optional($this->stable->started_at)->toDateTimeString();
    }
}
