@props([
    'name' => '',
    'label' => '',
])

<div class="flex flex-col">
    <label class="text-gray-900 font-semibold text-2sm" for="{{ $name }}">{{ $label }}:</label>
</div>
