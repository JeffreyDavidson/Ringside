@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => null,
])

<div class="flex items-center gap-2">
    <input type="checkbox"
        {{ $attributes->merge([
                'id' => $name,
                'name' => $name,
            ])->class([
                'h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary focus:ring-2',
            ]) }}>
    @if($label)
        <label for="{{ $name }}" class="text-2sm font-medium text-gray-700">{{ $label }}</label>
    @endif
</div>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
