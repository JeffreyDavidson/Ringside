<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Stables\StableForm;
use App\Models\Stable;
use App\Traits\Data\PresentsManagersList;
use App\Traits\Data\PresentsTagTeamsList;
use App\Traits\Data\PresentsWrestlersList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends BaseModal<StableForm, Stable>
 */
class FormModal extends BaseModal
{
    use PresentsManagersList;
    use PresentsTagTeamsList;
    use PresentsWrestlersList;

    protected string $modalLanguagePath = 'stables';

    protected string $modalFormPath = 'stables.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->value();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
    }
}
