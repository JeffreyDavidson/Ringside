<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">{{ $title }}</h3>
    </div>
    @isset($actions)
        <div class="kt-subheader__toolbar">
            {{ $actions }}
        </div>
    @endisset
</div>
