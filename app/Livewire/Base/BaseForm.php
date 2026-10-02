<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Services\Promotions\PromotionContextService;
use App\Support\ModelKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * @template TModel of Model
 */
abstract class BaseForm extends Form
{
    #[Locked]
    public int|string|null $modelId = null;

    /** @param TModel|null $formModel */
    public function setModel(?Model $formModel): void
    {
        if (! $formModel instanceof Model) {
            $this->modelId = null;

            return;
        }

        $this->modelId = ModelKey::of($formModel);
        $this->fill($formModel->getAttributes());
        $this->loadModelData($formModel);
    }

    public function isCreating(): bool
    {
        return $this->modelId === null;
    }

    public function isEditing(): bool
    {
        return $this->modelId !== null;
    }

    /**
     * Uniqueness among the records of the active promotion only, so other promotions' values neither collide
     * nor leak. Editing resolves the model through the promotion-scoped query, so the active promotion is the
     * model's own promotion.
     */
    protected function uniqueInPromotion(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)
            ->ignore($this->modelId)
            ->where('promotion_id', app(PromotionContextService::class)->current()?->id);
    }

    /** Existence among the records of the active promotion only. */
    protected function existsInPromotion(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->where('promotion_id', app(PromotionContextService::class)->current()?->id);
    }

    /** @param TModel $model */
    protected function loadModelData(Model $model): void {}

    /** @return array<string, array<int, mixed>> */
    abstract protected function rules(): array;

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [];
    }
}
