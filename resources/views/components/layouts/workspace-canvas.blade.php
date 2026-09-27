<section
    {{
        $attributes->merge([
            'class' => 'min-h-full w-full min-w-0',
        ])
    }}
    aria-label="Content workspace"
>
    {{ $slot }}
</section>
