<div class="pt-6 border-0 card-header">
    <div class="card-title">
        <x-search />
    </div>

    <div class="card-toolbar">
        <div class="d-flex justify-content-end" data-kt-venue-table-toolbar="base">
            <x-buttons.create route="{{ route('managers.create') }}" resource="Manager" />
        </div>

        @if (count($selected) > 0)
            <x-buttons.delete-selected :selected=$selected />
        @endif
    </div>
</div>
