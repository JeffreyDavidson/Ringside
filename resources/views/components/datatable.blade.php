<div class="table-responsive">
    <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
        <x-slot name="head">
            {{ $head }}
        </x-slot>

        <x-slot name="body">
            {{ $body }}
        </x-slot>
    </x-table>
</div>

@if ($footer)
    {{ $footer }}
@endif
