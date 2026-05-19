@props([
    'name' => null,
    'label' => '',
])

<x-form.label :for="$name" {{ $attributes }}>{{ $label }}</x-form.label>
