@props([
    'size' => null,
])

@php
    $viewData = get_defined_vars();
    $footer = $viewData['footer'] ?? null;
@endphp

<div
    {{
        $attributes->class([
            'relative mx-auto rounded-xl bg-white flex flex-col outline-none box-shadow-modal lg:top-[15%]',
            'max-w-[400px]' => $size === 'sm',
            'max-w-[800px]' => $size === null || $size === '',
            'max-w-[1100px]' => $size === 'lg',
        ])
    }}
    style="z-index: 90"
>
    <x-modal.header />
    <x-modal.body> {{ $slot }} </x-modal.body>
    @if ($footer instanceof \Illuminate\View\ComponentSlot && $footer->isNotEmpty())
        <x-modal.footer> {{ $footer }} </x-modal.footer>
    @endif
</div>
