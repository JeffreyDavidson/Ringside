<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class CanChangeActivationDate implements ValidationRule
{
    public function __construct(private Title|Stable|null $model) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  DateTimeInterface|string|null  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model && ($this->model->isCurrentlyActive() && ! $this->model->wasActiveOn(Carbon::parse($value)))) {
            $nameLabel = $this->model instanceof Title ? $this->model->getDisplayName() : $this->model->name;
            $fail('activations.validation.activation_active')->translate(['name' => $nameLabel]);
        }
    }
}
