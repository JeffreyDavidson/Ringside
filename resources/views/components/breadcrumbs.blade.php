<div {{ $attributes->merge(['class' => 'flex items-center gap-1.25 text-xs lg:text-sm font-medium mb-2.5 lg:mb-0']) }}>
    @foreach (Navigation::make()->breadcrumbs() as $breadcrumb)
        <span class="text-gray-700">
            {{ $breadcrumb['title'] }}
        </span>

        @if ($loop->remaining !== 0)
            <i class="ki-filled ki-right text-gray-500 text-3xs"></i>
        @endif
    @endforeach
</div>
