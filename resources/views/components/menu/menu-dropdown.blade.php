@props([
    'isDefault' => false
])

<div {{ $attributes->merge(['class' => 'gap-0.5 border border-solid border-gray-200 shadow-[0_7px_18px_0_rgba(0,0,0,0.09)] bg-white rounded-xl'])->class([
    'py-2.5' => $isDefault
]) }}>
    {{ $slot }}
</div>
