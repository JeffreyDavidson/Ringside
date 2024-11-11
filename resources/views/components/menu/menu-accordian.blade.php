<div {{ $attributes->merge(['class' => 'menu-accordion gap-0.5 pl-[10px] relative before:absolute before:left-[20px] before:top-0 before:bottom-0 before:border-l before:border-gray-200']) }} style="display: none;">
    {{ $slot }}
</div>
