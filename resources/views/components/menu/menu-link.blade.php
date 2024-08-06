@props(["text", "icon", "active" => false])

<a {{ $attributes->class(["menu-link", "active" => $active]) }}>
    @isset($icon)
        <span class="menu-icon">
            {{ $icon }}
        </span>
    @endisset

    <span class="menu-title">{{ $text }}</span>
</a>
