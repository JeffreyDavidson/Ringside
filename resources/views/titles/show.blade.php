<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Title Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('titles.index')" label="Titles" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$title->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-page>
        <x-details-card>
            <x-card>
                <x-card.body>
                    <x-card.detail-link collapsibleLink="kt_title_view_details" resource="title" :href="route('titles.edit', $title)" />
                    <x-separator />
                    <x-card.detail-container id="kt_title_view_details">
                        <x-card.detail-row property="Name" value="{{ $title->name }}" />
                        <x-card.detail-row property="Activation Date" value="{{ $title->activatedAt?->toDateString() ?? 'Unscheduled' }}" />
                    </x-card.detail-container>

                    @if ($title->isUnactivated())
                        <x-notice class="mt-4" title="This title needs your attention!" description="This title does not have an activation date and needs to be activated." />
                    @endif
                </x-card.body>
            </x-card>
        </x-details-card>

        <x-details-data>
            <livewire:titles.title-championships-list :title="$title" />
        </x-details-data>
    </x-details-page>
</x-layouts.app>
