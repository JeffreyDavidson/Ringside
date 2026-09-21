@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $fieldValue = $type === 'password' ? null : old($name, $value);
    $fieldValue = is_string($fieldValue) ? $fieldValue : '';
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? $name.'-hint' : null, $hasError ? $name.'-error' : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
        <label for="{{ $name }}" class="text-ringside-muted-bright text-sm font-semibold">{{ $label }}</label>
        @isset($action)
            {{ $action }}
        @endisset
    </div>
    <x-form.input
        :name="$name"
        :id="$name"
        :type="$type"
        :value="$fieldValue"
        appearance="ringside"
        :aria-invalid="$hasError ? 'true' : 'false'"
        :aria-describedby="$describedBy !== '' ? $describedBy : null"
        {{ $attributes }}
    />
    @if ($hint)
        <p id="{{ $name }}-hint" class="text-ringside-muted text-sm leading-relaxed">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" role="alert" class="text-ringside-signal-soft text-sm leading-relaxed">
            {{ $message }}
        </p>
    @enderror
</div>
