<div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
    <div class="card-header">
        {{  $header }}
    </div>
    <div class="card-body p-9">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
