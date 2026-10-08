<section
    {{
        $attributes->merge([
            'class' => 'min-h-full w-full min-w-0',
        ])
    }}
    aria-label="{{ __('navigation.content_workspace') }}"
>
    {{ $slot }}
</section>
