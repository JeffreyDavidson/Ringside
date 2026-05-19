@props(['title', 'subtitle' => null, 'actions' => null])

<div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <h1 class="text-lg font-semibold text-gray-900">{{ $title }}</h1>
        @if($subtitle)
            <span class="text-sm text-gray-600">{{ $subtitle }}</span>
        @endif
    </div>
    
    @if($actions)
        <div class="flex items-center gap-2.5">
            {{ $actions }}
        </div>
    @endif
</div>