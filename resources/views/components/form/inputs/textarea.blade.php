@props(['placeholder', 'name', 'label', 'value'])

<div class="flex flex-center gap-1">
    <label class="text-gray-900 font-semibold text-2sm" for="{{ $name }}">{{ $label }}:</label>
</div>

<textarea
    class="block w-full appearance-none shadow-none outline-none font-medium text-2sm bg-light-active rounded-md py-[.55rem] px-3 border border-solid border-gray-300 text-gray-700 h-[100px] hover:border-gray-400 focus:border-primary focus:shadow-form-input-focus-box-shadow focus:text-gray-700"
    {{ $attributes->whereStartsWith('wire:model') }}>
    name="{{ $name }}" :placeholder="$label ?? Enter {!! $label !!}: null">
</textarea>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
