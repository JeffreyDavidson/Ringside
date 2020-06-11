<?php

namespace App\ViewModels;

use App\Models\Wrestler;
use Spatie\ViewModels\ViewModel;

class WrestlerViewModel extends ViewModel
{
    /** @var $wrestler */
    public $wrestler;

    /**
     * Create a new wrestler view model instance.
     *
     * @param App\Models\Wrestler|null $wrestler
     */
    public function __construct(Wrestler $wrestler = null)
    {
        $this->wrestler = $wrestler ?? new Wrestler;
    }
}
