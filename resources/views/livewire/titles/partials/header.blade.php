<div class="pt-6 border-0 card-header">
    <div class="card-title">
        <x-search />
    </div>

    <div class="card-toolbar">
        <div class="d-flex justify-content-end">
            <x-buttons.create route="{{ route('titles.create') }}" resource="Title" />
        </div>

        @if (count($selected) > 0)
            <x-buttons.delete-selected :selected=$selected />
        @endif
    </div>
</div>
