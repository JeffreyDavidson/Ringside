<div class="kt-section">
    @isset($title)
        <h3 class="kt-section__title">{{ $title }}:</h3>
    @endisset
    <div class="kt-section_content">
        {{ $slot }}
    </div>
</div>
