<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Stables\Stable;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<CreateEditForm, Stable>
 */
class FormModal extends BaseFormModal
{
    use PresentsManagersList;
    use PresentsTagTeamsList;
    use PresentsWrestlersList;


    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Stable::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.stables.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'start_date' => fn () => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.stables.modals.form-modal');
    }
}
