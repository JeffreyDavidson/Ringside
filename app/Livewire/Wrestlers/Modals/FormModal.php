<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Wrestlers\WrestlerForm;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends BaseModal<WrestlerForm, Wrestler>
 */
class FormModal extends BaseModal
{
    protected string $modalFormPath = 'wrestlers.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        /**
         * @var string $state
         *
         * @phpstan-ignore-next-line
         */
        $state = fake('en_US')->state();

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->value();
        $this->modelForm->hometown = fake()->city().', '.$state;
        $this->modelForm->height_feet = fake()->numberBetween(5, 7);
        $this->modelForm->height_inches = fake()->numberBetween(0, 11);
        $this->modelForm->weight = fake()->numberBetween(180, 400);
        $this->modelForm->signature_move = Str::of(fake()->optional(0.8)->sentence(3))->title()->value();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
    }
}
