<div
    {{ $attributes->class('border-border bg-surface-header text-foreground flex flex-col overflow-hidden rounded-none border') }}
    data-card
>
    {{-- Header Section --}}
    @isset($header)
        <header class="card-header border-border border-b px-6 py-4">{{ $header }}</header>
    @endisset

    {{-- Body Section --}}
    @if (isset($body))
        <div class="card-body p-6">{{ $body }}</div>
    @elseif ($slot->isNotEmpty())
        {{ $slot }}
    @endif

    {{-- Footer Section --}}
    @isset($footer)
        <footer class="card-footer border-border border-t px-6 py-4">{{ $footer }}</footer>
    @endisset
</div>
