@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? '',
    'label' => 'Value',
    'options' => [],
    'selected' => '',
])

<x-form.form-label :$name :$label />

<select
    class="font-medium text-2sm leading-none bg-light-active rounded-md h-10 ps-3 pe-3 border border-solid border-gray-300 text-gray-700 focus:border-primary"
    name="{{ $name }}" {{ $attributes->whereStartsWith('wire:click') }}
    {{ $attributes->whereStartsWith('wire:model') }}>
    <option value="">Select</option>
    @foreach ($options as $key => $value)
        <option value="{{ $key }}" @selected($selected == $key)>{{ $value }}</option>
    @endforeach
</select>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
