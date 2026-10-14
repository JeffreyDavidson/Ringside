@props(['title', 'subtitle' => null, 'actions' => null])

<header class="flex flex-wrap items-end justify-between gap-4" data-test="index-page-header">
    <div class="flex flex-col justify-center gap-1">
        <h1 class="font-display text-foreground text-3xl leading-none tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <span class="text-muted-foreground text-sm">{{ $subtitle }}</span>
        @endif
    </div>

    @if ($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endif
</header>
