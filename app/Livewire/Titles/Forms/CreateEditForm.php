<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Forms;

use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Livewire\Base\BaseForm;
use App\Models\Titles\Title;
use App\Rules\Shared\CanChangeDebutDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Title> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    /** Kept as a string so Livewire does not auto-cast the enum value. */
    public ?string $type = '';

    /** Kept as a string so Livewire does not auto-cast the date. */
    public ?string $start_date = '';

    protected function loadModelData(Model $model): void
    {
        $this->start_date = $model->firstActivityPeriod?->started_at?->toDateString();
    }

    public function toData(): TitleData
    {
        return new TitleData(
            name: $this->name,
            type: TitleType::from((string) $this->type),
            debut_date: $this->start_date ? Carbon::parse($this->start_date) : null,
        );
    }

    public function title(): Title
    {
        return Title::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $title = $this->isEditing() ? $this->title() : null;

        return [
            'name' => ['required', 'string', 'max:255', 'ends_with:Title,Titles', $this->uniqueInPromotion('titles', 'name')],
            'type' => ['required', Rule::enum(TitleType::class)],
            'start_date' => ['nullable', 'date', new CanChangeDebutDate($title)],
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'type' => 'title type',
            'start_date' => 'start date',
        ];
    }
}
