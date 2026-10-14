@props(['route', 'label'])

<a
    class="text-foreground decoration-border-strong hover:decoration-signal focus-visible:outline-foreground underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2"
    href="{{ $route }}"
>{{ $label }}</a>
