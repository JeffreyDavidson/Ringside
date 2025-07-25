<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Stables\Stable;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Stable>
 */
class FormModal extends BaseFormModal
{
    use GeneratesDummyData;
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
            'start_date' => fn () => $this->generateOptionalStartDate(),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Stable';
        }

        return 'Create Stable';
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.stables.modals.form-modal');
    }
}
