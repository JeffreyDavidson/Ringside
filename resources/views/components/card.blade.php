@props([
    'variant' => 'default',
    'class' => '',
])

@php
$classes = collect([
    // Base card classes
    'bg-white rounded-lg border border-gray-200 overflow-hidden',
    
    // Variant classes
    match($variant) {
        'bordered' => 'border-2 border-gray-300 shadow-sm',
        'elevated' => 'shadow-lg border-gray-100',
        default => 'shadow-sm'
    }
])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes . ' ' . $class]) }} data-card>
    {{-- Header Section --}}
    @isset($header)
        <header class="card-header border-b border-gray-200 px-6 py-4 bg-gray-50">
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
        <footer class="card-footer border-t border-gray-200 px-6 py-4 bg-gray-50">
            {{ $footer }}
        </footer>
    @endisset
</div>