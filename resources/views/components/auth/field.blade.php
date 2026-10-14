@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $fieldValue = $type === 'password' ? null : old($name, $value);
    $fieldValue = is_string($fieldValue) ? $fieldValue : '';
    $hasError = $errors->has($name);
    $describedBy = collect([$hint ? $name.'-hint' : null, $hasError ? $name.'-error' : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
        <label for="{{ $name }}" class="text-muted-foreground-bright text-sm font-semibold">{{ $label }}</label>
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
        <p id="{{ $name }}-hint" class="text-muted-foreground text-sm leading-relaxed">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" role="alert" class="text-error flex items-start gap-1.5 text-sm leading-relaxed">
            <x-heroicon-s-exclamation-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" data-error-icon />
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
