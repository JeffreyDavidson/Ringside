@props(["text", "icon"])

<a {{ $attributes->class(["menu-link"]) }}>
    @isset($icon)
        <span class="menu-icon">
            {{ $icon }}
        </span>
    @endisset

    <span class="menu-title">{{ $text }}</span>
</a>
