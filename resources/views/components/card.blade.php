<div {{ $attributes->class(['card']) }}>
    @isset($header)
        {{ $header }}
    @endif

    {{ $slot }}

    @isset($footer)
        {{ $footer }}
    @endif
</div>
