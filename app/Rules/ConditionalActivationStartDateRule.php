<?php

namespace App\Rules;

use Illuminate\Support\Arr;
use Illuminatech\Validation\Composite\CompositeRule;

class ConditionalActivationStartDateRule extends CompositeRule
{
    protected $model;

    /**
     * @var string validation error message from particular underlying validator.
     */
    private $message;

    public function __construct($model)
    {
        $this->model = $model;
    }

    protected function rules($startedAt = null): array
    {
        return array_merge($startedAt ? ['string', 'date_format:Y-m-d H:i:s'] : [], [
            new ActivationStartDateCanBeChanged($this->model),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value): bool
    {
        $data = [];

        // ensure correct validation for array attributes like 'item_ids.*' or 'items.*.id'
        Arr::set($data, $attribute, $value);

        $validator = $this->getValidatorFactory()->make(
            $data,
            [
                $attribute => $this->rules($value),
            ],
            $this->messages()
        );

        if ($validator->fails()) {
            $this->message = $validator->getMessageBag()->first();

            return false;
        }

        return true;
    }

    protected function messages(): array
    {
        return [
            'string' => 'Only string is allowed.',
            'date_format' => ':attribute is too short.',
        ];
    }

    public function message()
    {
        return $this->message;
    }
}
