<?php

namespace App\ViewModels;

use App\Models\Manager;
use Spatie\ViewModels\ViewModel;

class ManagerViewModel extends ViewModel
{
    /** @var $manager */
    public $manager;

    /**
     * Create a new manager view model instance.
     *
     * @param App\Models\Manager|null $manager
     */
    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager ?? new Manager;
        $this->manager->started_at = optional($this->manager->started_at)->toDateTimeString();
    }
}
