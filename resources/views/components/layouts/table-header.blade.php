@props(['title', 'subtitle' => null, 'actions' => null])

<div class="border-ringside-line flex flex-wrap items-end justify-between gap-4 border-b pb-5">
    <div class="flex flex-col justify-center gap-1">
        <h1 class="font-display text-ringside-ink text-3xl leading-none tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <span class="text-ringside-muted text-sm">{{ $subtitle }}</span>
        @endif
    </div>

    @if ($actions)
        <div class="flex items-center gap-3">{{ $actions }}</div>
    @endif
</div>
