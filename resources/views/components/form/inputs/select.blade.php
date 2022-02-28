<label for="{{ $name }}">{{ $label }}:</label>

<select
    class="form-control form-select @error($name) is-invalid @enderror"
    name="{{ $name }}"
    id="{{ $name }}-dropdown"
    {{ $attributes->whereStartsWith('wire:click') }}
    {{ $attributes->whereStartsWith('wire:model') }}
>
    <option value="">Select</option>
    @foreach ($options as $key => $value)
        <option
            value="{{ $key }}"
            @if ($selected === $key) selected @endif
        >{{ $value }}</option>
    @endforeach
</select>

@error($name)
    <x-form.validation-error name="{{ $name }}" :message="$message" />
@enderror
