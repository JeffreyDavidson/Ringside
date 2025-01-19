<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Stables\StableForm;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    protected string $modelType = Stable::class;

    protected string $modalLanguagePath = 'stables';

    protected string $modalFormPath = 'stables.modals.form-modal';

    public StableForm $modelForm;

    public function fillDummyFields()
    {
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->name = Str::title(fake()->words(2, true));
        $this->modelForm->start_date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
    }
}
