<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Referee Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('referees.index')" label="Referees" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$referee->full_name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_referee_view_details" resource="referee" :href="route('referees.edit', $referee)" />
                <x-separator />
                <x-card.detail-container id="kt_referee_view_details">
                    <x-card.detail-row property="Name" value="{{ $referee->full_name }}" />
                    <x-card.detail-row property="Start Date" value="{{ $referee->startedAt?->toDateString() ?? 'No Start Date Set' }}" />
                </x-card.detail-container>

                @if ($referee->isUnemployed())
                    <x-notice class="mt-4" title="This referee needs your attention!" description="This referee does not have a start date and needs to be employed." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
