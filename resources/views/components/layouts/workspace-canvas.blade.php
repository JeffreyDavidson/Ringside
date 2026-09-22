<section
    {{
        $attributes->merge([
            'class' => 'min-h-full w-full min-w-0 border border-dashed border-ringside-line',
        ])
    }}
    aria-label="Content workspace"
>
    {{ $slot }}
</section>
