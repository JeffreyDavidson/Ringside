<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Stable Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$stable->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_stable_view_details" resource="stable" :href="route('stables.edit', $stable)" />
                <x-separator />
                <x-card.detail-container id="kt_stable_view_details">
                    <x-card.detail-row property="Name" value="{{ $stable->name }}" />
                    <x-card.detail-row property="Start Date" value="{{ $stable->activatedAt?->toDateString() ?? 'No Activation Date Set' }}" />
                </x-card.detail-container>

                @if ($stable->isUnactivated())
                    <x-notice class="mt-4" title="This stable needs your attention!" description="This stable does not have a start date and needs to be started." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
