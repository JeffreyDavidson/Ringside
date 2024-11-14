@props(['inGrid' => false])

<div
    {{ $attributes->class(['flex flex-col shadow-[0_3px_4px_0px_rgba(0,0,0,0.03)] bg-white rounded-lg border border-solid border-gray-200']) }}>
    {{ $slot }}
</div>
