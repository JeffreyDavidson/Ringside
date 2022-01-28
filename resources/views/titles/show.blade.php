<x-layouts.app>
    <x-sub-header :title="$title->name">
        <x-slot name="actions">
            <a href="{{ route('titles.index') }}" class="btn btn-label-brand btn-bold">
                Back To Titles
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        @empty($title->activated_at)
            <div class="alert alert-warning" role="alert">
                <strong>Warning!</strong>&nbsp;This title is not activated!
            </div>
        @endempty
        <div class="kt-grid kt-grid--desktop kt-grid--ver kt-grid--ver-desktop kt-app">
            <div id="kt_profile_aside" class="kt-grid__item kt-app__toggle kt-app__aside">
                <x-portlet title="Information" headBorder="false">
                    <div class="kt-portlet__head kt-portlet__head--noborder">
                        <div class="kt-portlet__head-label">
                            <h3 class="kt-portlet__head-title"></h3>
                            <div class="kt-portlet__head-toolbar"></div>
                        </div>
                    </div>
                    <div class="kt-portlet__body kt-portlet__body--fit-y">
                        <div class="kt-widget kt-widget--user-profile-1">
                            <div class="kt-widget__head">
                                <div class="kt-widget__media">
                                    <img src="https://via.placeholder.com/100" alt="image">
                                </div>
                                <div class="kt-widget__content">
                                    <div class="kt-widget__section">
                                        <span class="kt-widget__username">{{ $title->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="kt-widget__body">
                                <div class="kt-widget__content">
                                    <div class="kt-widget__info">
                                        @isset($title->activated_at)
                                            <span class="kt-widget__label">Date Introduced:</span>
                                            <span class="kt-widget__data">{{ $title->activated_at->toDateString() }}</span>
                                        @endisset
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-portlet>
            </div>
            <div class="flex-row-fluid ml-lg-8">
                <div class="row">
                    <x-portlet title="Championships" headBorder="false">
                        <x-data-table :collection="$title->championships()->paginate()">
                            <thead>
                                <th>New Champion</th>
                                <th>Previous Champion</th>
                                <th>Event Name</th>
                                <th>Event Date</th>
                            </thead>
                            <tbody>
                                @forelse($title->championships as $championship)
                                    <tr>
                                        <td>{{ $championship->champion->name }}</td>
                                        <td>{{ $championship->previousChampion?->name ?? 'First Champion' }}</td>
                                        <td>{{ $championship->match->event->name }}</td>
                                        <td>{{ $championship->match->event->date->toDateString() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">No matching records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </x-datatable>
                    </x-portlet>
                </div>
            </div>
        </div>
    </x-content>
</x-layouts.app>
