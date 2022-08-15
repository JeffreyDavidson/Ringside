<li class="breadcrumb-item text-muted">
    @isset ($url)
    <a href="{{ $url }}" class="text-muted text-hover-primary">{{ $label }}</a>
    @else
    {{ $label }}
    @endisset
</li>
