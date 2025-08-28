@props([
    'name' => null,
])

@error($name)
    <span {{ $attributes->merge([
        'class' => 'font-medium text-xs leading-4 text-red-500 mt-1 block'
    ]) }}>
        {{ $message }}
    </span>
@enderror