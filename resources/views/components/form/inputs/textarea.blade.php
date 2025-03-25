@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'placeholder' => 'Enter ' . $label,
])

<x-form.form-label :$name :$label />

<textarea
    {{ $attributes->merge([
            'id' => $name,
            'name' => $name,
        ])->class([
            'block w-full appearance-none shadow-none outline-none font-medium text-2sm bg-light-active rounded-md py-[.55rem] px-3 border border-solid border-gray-300 text-gray-700 h-[100px] hover:border-gray-400 focus:border-primary focus:shadow-form-input-focus-box-shadow focus:text-gray-700',
        ]) }}>
    {{ $placeholder }}
</textarea>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
