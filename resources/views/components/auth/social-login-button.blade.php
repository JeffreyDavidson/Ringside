@props([
    'provider' => 'google',
    'href' => '#',
])

@php
    $configs = [
        'google' => [
            'text' => 'Use Google',
            'icon' => 'google.svg',
        ],
        'apple' => [
            'text' => 'Use Apple',
            'icon_light' => 'apple-white.svg',
            'icon_dark' => 'apple-black.svg',
        ]
    ];

    $config = $configs[$provider] ?? $configs['google'];
@endphp

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'flex items-center justify-center h-8.5 px-3 font-medium text-2sm leading-[var(--text-sm--line-height)] gap-1.5 rounded-md border border-solid border-[var(--input)] bg-white text-secondary-foreground hover:bg-muted focus:bg-muted shadow-[var(--tw-input-box-shadow)] transition-[color,box-shadow] cursor-pointer shrink-0'
]) }}>
    @if($provider === 'google')
        <img alt="Google" class="size-3.5 shrink-0" src="{{ Vite::asset('resources/media/brand-logos/'.$config['icon']) }}" />
    @elseif($provider === 'apple')
        <img alt="Apple" class="size-3.5 shrink-0 dark:hidden" src="{{ Vite::asset('resources/media/brand-logos/'.$config['icon_light']) }}" />
        <img alt="Apple" class="size-3.5 shrink-0 hidden dark:block" src="{{ Vite::asset('resources/media/brand-logos/'.$config['icon_dark']) }}" />
    @endif
    {{ $config['text'] }}
</a>
