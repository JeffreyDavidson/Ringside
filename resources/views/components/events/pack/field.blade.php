@props(['name', 'label', 'value' => null, 'type' => 'text', 'max' => null])
<label class="pack-field">
    <span>{{ $label }}</span>
    @if ($type === 'textarea')
        <textarea name="{{ $name }}" rows="3" @if ($max) maxlength="{{ $max }}" @endif>{{ $value }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @if ($type === 'number') min="1" max="1440" @endif
            @if ($max) maxlength="{{ $max }}" @endif
        />
    @endif
</label>
