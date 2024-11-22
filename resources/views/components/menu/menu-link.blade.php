@aware([
    'isDefault' => false,
])

<a {{ $attributes->merge(['class' => 'flex items-center grow cursor-pointer p-0 m-0'])->class([
    'ms-2.5 me-2.5 p-2.5 rounded-md' => $isDefault,
]) }}>
    {{ $slot }}
</a>
