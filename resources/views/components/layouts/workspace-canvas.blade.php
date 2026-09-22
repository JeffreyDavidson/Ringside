<section
    {{
        $attributes->merge([
            'class' => 'min-h-full border border-dashed border-ringside-line',
        ])
    }}
    aria-label="Content workspace"
>
    {{ $slot }}
</section>
