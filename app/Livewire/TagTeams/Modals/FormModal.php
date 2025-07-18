<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Livewire\Base\BaseForm;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<CreateEditForm, TagTeam>
 */
class FormModal extends BaseFormModal
{
    use PresentsWrestlersList;

    public BaseForm $form;

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return TagTeam::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.tag-teams.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /** @var Wrestler $wrestlerA */
        /** @var Wrestler $wrestlerB */
        [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();

        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'start_date' => fn () => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
            'wrestlerA' => fn () => $wrestlerA->id,
            'wrestlerB' => fn () => $wrestlerB->id,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.tag-teams.modals.form-modal');
    }
}
