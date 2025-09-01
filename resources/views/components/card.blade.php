@props([
    'variant' => 'default',
    'class' => '',
])

@php
$classes = collect([
    // Base card classes using Metronic design tokens
    'bg-card text-card-foreground border border-border overflow-hidden flex flex-col',
    'rounded-[calc(var(--radius)+4px)] shadow-[0_1px_2px_0_rgba(0,0,0,0.05)]',
    
    // Variant classes
    match($variant) {
        'bordered' => 'border-2 border-border',
        'elevated' => 'shadow-lg border-border/50',
        default => ''
    }
])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes . ' ' . $class]) }} data-card>
    {{-- Header Section --}}
    @isset($header)
        <header class="card-header border-b border-border px-6 py-4 bg-muted">
            {{ $header }}
        </header>
    @endisset

    {{-- Body Section --}}
    @if(isset($body))
        <div class="card-body p-6">
            {{ $body }}
        </div>
    @elseif($slot->isNotEmpty())
        {{ $slot }}
    @endif

    {{-- Footer Section --}}
    @isset($footer)
        <footer class="card-footer border-t border-border px-6 py-4 bg-muted">
            {{ $footer }}
        </footer>
    @endisset
</div>