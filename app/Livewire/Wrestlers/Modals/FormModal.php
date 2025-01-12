<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Wrestlers\WrestlerForm;
use App\Models\Wrestler;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    protected string $modelType = Wrestler::class;

    protected string $modalLanguagePath = 'wrestlers';

    protected string $modalFormPath = 'wrestlers.modals.form-modal';

    public WrestlerForm $modelForm;

    public function fillDummyFields()
    {
        $this->modelForm->name = Str::title(fake()->words(2, true));
        $this->modelForm->hometown = fake()->city().', '.fake()->state();
        $this->modelForm->height_feet = fake()->numberBetween(5, 8);
        $this->modelForm->height_inches = fake()->numberBetween(0, 11);
        $this->modelForm->weight = fake()->numberBetween(180, 400);
        $this->modelForm->signature_move = Str::title(fake()->optional(0.8)->words(3, true));
        $this->modelForm->start_date = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');
    }
}
