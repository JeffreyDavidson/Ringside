<?php

declare(strict_types=1);

namespace App\View\Forms;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

final readonly class FormFieldContext
{
    public function __construct(
        public ?string $name,
        public ?string $id,
        public bool $hasError,
        public string $describedBy,
    ) {}

    public static function from(
        mixed $name,
        ComponentAttributeBag $attributes,
        ViewErrorBag $errors,
    ): self {
        $fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();

        if ($fieldName !== null && ! is_string($fieldName)) {
            throw new InvalidArgumentException('Form field names must be strings.');
        }

        if ($fieldName && str_contains($fieldName, '=')) {
            $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
        }

        $id = $attributes->get('id', $fieldName);
        if ($id !== null && ! is_string($id)) {
            throw new InvalidArgumentException('Form field IDs must be strings.');
        }

        $hasError = $fieldName !== null && $fieldName !== '' && $errors->has($fieldName);
        $errorId = $id.'-error';
        $description = $attributes->get('aria-describedby');
        $description = is_string($description) ? $description : '';
        $describedBy = collect(preg_split('/\s+/', trim($description)) ?: [])
            ->merge($hasError ? [$errorId] : [])
            ->filter()
            ->unique()
            ->implode(' ');

        return new self($fieldName, $id, $hasError, $describedBy);
    }
}
