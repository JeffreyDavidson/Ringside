<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Manager Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('managers.index')" label="Managers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$manager->full_name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_manager_view_details" resource="manager" :href="route('managers.edit', $manager)" />
                <x-separator />
                <x-card.detail-container id="kt_manager_view_details">
                    <x-card.detail-row property="Name" value="{{ $manager->full_name }}" />
                    <x-card.detail-row property="Start Date" value="{{ $manager->startedAt?->toDateString() ?? 'No Start Date Set' }}" />
                </x-card.detail-container>

                @if ($manager->isUnemployed())
                    <x-notice class="mt-4" title="This manager needs your attention!" description="This manager does not have a start date and needs to be employed." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
