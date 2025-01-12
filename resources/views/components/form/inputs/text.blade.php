<div class="flex flex-center gap-1">
    <label class="text-gray-900 font-semibold text-2sm" for="{{ $name }}">{{ $label }}:</label>
</div>

<label
    class="flex gap-1.5 items-center appearance-none shadow-none outline-none text-gray-600 w-full font-medium text-2sm leading-none bg-light-active rounded-md h-10 ps-3 pe-3 border border-solid border-gray-300 hover:border-gray-400 has-[:focus]:border-primary has-[:focus]:text-gray-700 has-[:invalid]:border-red-600">
    <input type="text"
        {{ $attributes->merge([
            'class' =>
                'grow bg-transparent border-transparent text-inherit outline-none m-0 p-0 text-2sm focus:ring-0 focus:border-none',
            'placeholder' => 'Enter ' . ($label ?? 'Value'),
        ]) }}>
</label>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
