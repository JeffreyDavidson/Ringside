<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Forms;

use App\Data\Promotions\PromotionData;
use App\Livewire\Base\BaseForm;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Promotion> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    public string $slug = '';

    public string $timezone = 'UTC';

    protected function loadModelData(Model $model): void
    {
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->timezone = $model->timezone;
    }

    public function toData(): PromotionData
    {
        return new PromotionData(
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
        );
    }

    public function promotion(): Promotion
    {
        return Promotion::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('promotions', 'slug')->ignore($this->modelId),
            ],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
