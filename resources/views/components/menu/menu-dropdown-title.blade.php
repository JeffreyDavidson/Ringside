@aware([
    'isDefault' => false
])

<span {{ $attributes->merge(['class' => 'flex items-center grow'])->class([
    'leading-[1.125rem font-medium] font-[.8125rem] text-gray-800' => $isDefault,
    'leading-4' => !$isDefault,
]) }}>
    {{ $slot }}
</span>
