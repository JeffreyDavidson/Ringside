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

    /** The edited record's own promotion, kept so validation never depends on the request's promotion context. */
    #[Locked]
    public ?int $modelPromotionId = null;

    /** @param TModel|null $formModel */
    public function setModel(?Model $formModel): void
    {
        if (! $formModel instanceof Model) {
            $this->modelId = null;
            $this->modelPromotionId = null;

            return;
        }

        $promotionId = $formModel->getAttribute('promotion_id');

        $this->modelId = ModelKey::of($formModel);
        $this->modelPromotionId = is_int($promotionId) ? $promotionId : null;
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
     * Uniqueness among the records of the form's promotion only (see formPromotionId()), so other promotions'
     * values neither collide nor leak.
     */
    protected function uniqueInPromotion(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)
            ->ignore($this->modelId)
            ->where('promotion_id', $this->formPromotionId());
    }

    /** Existence among the records of the form's promotion only (see formPromotionId()). */
    protected function existsInPromotion(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->where('promotion_id', $this->formPromotionId());
    }

    /**
     * The promotion the form's record belongs to: the edited record's own promotion, or, when creating, the
     * promotion the BelongsToPromotion creating hook will assign (the enforced context). A global administrator
     * without an enforced context creates unowned records, so null then compares against unowned records.
     */
    protected function formPromotionId(): ?int
    {
        if ($this->isEditing()) {
            return $this->modelPromotionId;
        }

        $context = app(PromotionContextService::class);

        return $context->isEnforced() ? $context->current()?->id : null;
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
