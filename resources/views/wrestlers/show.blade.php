<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Wrestler Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$wrestler->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_wrestler_view_details" resource="wrestler" :href="route('wrestlers.edit', $wrestler)" />
                <x-separator />
                <x-card.detail-container id="kt_wrestler_view_details">
                    <x-card.detail-row property="Name" value="{{ $wrestler->name }}" />
                    <x-card.detail-row property="Height" :value="$wrestler->height" />
                    <x-card.detail-row property="Weight" value="{{ $wrestler->weight }} lbs." />
                    <x-card.detail-row property="Hometown" value="{{ $wrestler->hometown }}" />
                    @if ($wrestler->signature_move)
                        <x-card.detail-row property="Signature Move" value="{{ $wrestler->signature_move }}" />
                    @endif
                    <x-card.detail-row property="Start Date" value="{{ $wrestler->startedAt?->toDateString() ?? 'No Start Date Set' }}" />
                </x-card.detail-container>

                @if ($wrestler->isUnemployed())
                    <x-notice class="mt-4" title="This wrestler needs your attention!" description="This wrestler does not have a start date and needs to be employed." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
