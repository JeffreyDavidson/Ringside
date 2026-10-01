@props(['title', 'link', 'linkLabel'])

<section {{ $attributes->class('flex min-w-0 flex-col gap-3') }}>
    <div class="flex items-center justify-between gap-4">
        <h2 class="font-display text-ringside-ink m-0 text-xl leading-none tracking-tight uppercase">{{ $title }}</h2>
        <a
            href="{{ $link }}"
            class="text-ringside-muted hover:text-ringside-ink focus-visible:outline-ringside-white inline-flex min-h-11 items-center gap-1 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
        >
            {{ $linkLabel }}
            <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
        </a>
    </div>

    {{ $slot }}
</section>
