@props([
    'size' => null,
])

<div {{
    $attributes->class([
        'relative mx-auto flex max-h-[calc(100dvh-2rem)] w-full flex-col overflow-hidden border border-ringside-line bg-ringside-surface-panel text-ringside-ink shadow-2xl outline-none',
        'max-w-[400px]' => $size === 'sm',
        'max-w-[800px]' => $size === null || $size === '',
        'max-w-[1100px]' => $size === 'lg',
    ])
}}>
    <x-modal.header />
    <x-modal.body> {{ $slot }} </x-modal.body>
    @if ($footer->isNotEmpty())
        <x-modal.footer> {{ $footer }} </x-modal.footer>
    @endif
</div>
