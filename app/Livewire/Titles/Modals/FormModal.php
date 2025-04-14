<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Titles\TitleForm;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends BaseModal<TitleForm, Title>
 */
final class FormModal extends BaseModal
{
    protected string $modalFormPath = 'titles.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void
    {
        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->append(' Title')->value();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
    }
}
