<?php

namespace App\ViewModels;

use App\Models\Referee;
use Spatie\ViewModels\ViewModel;

class RefereeViewModel extends ViewModel
{
    /** @var $referee */
    public $referee;

    /**
     * Create a new referee view model instance.
     *
     * @param App\Models\Referee|null $referee
     */
    public function __construct(Referee $referee = null)
    {
        $this->referee = $referee ?? new Referee;
        $this->referee->started_at = optional($this->referee->started_at)->toDateTimeString();
    }
}
