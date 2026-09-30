@props(['route', 'label'])

<a
    class="text-ringside-ink decoration-ringside-line-bright hover:decoration-ringside-signal focus-visible:outline-ringside-white underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2"
    href="{{ $route }}"
>{{ $label }}</a>
