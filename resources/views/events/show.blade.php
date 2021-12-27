<x-layouts.app>
    <x-sub-header :title="$event->name">
        <x-slot name="actions">
            <a
                href="{{ route('events.index') }}"
                class="btn btn-label-brand btn-bold"
            >
                Back To Events
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        @if ($event->isUnscheduled())
            <div class="alert alert-warning" role="alert">
                <strong>Warning!</strong>&nbsp;This event is not scheduled!
            </div>
        @endif
        <div
            class="kt-grid kt-grid--desktop kt-grid--ver kt-grid--ver-desktop kt-app"
        >
            <div
                id="kt_profile_aside"
                class="kt-grid__item kt-app__toggle kt-app__aside"
            >
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
                                    <img
                                        src="https://via.placeholder.com/100"
                                        alt="image"
                                    />
                                </div>
                                <div class="kt-widget__content">
                                    <div class="kt-widget__section">
                                        <span
                                            class="kt-widget__username"
                                            >{{ $event->name }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="kt-widget__body">
                                <div class="kt-widget__content">
                                    @if ($event->isScheduled() || $event->isPast())
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label"
                                                >Date:</span
                                            >
                                            <span
                                                class="kt-widget__data"
                                                >{{ $event->date->toDateString() }}</span
                                            >
                                        </div>
                                    @endif
                                    @isset ($event->venue)
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label"
                                                >Venue:</span
                                            >
                                            <span
                                                class="kt-widget__data"
                                                >{{ $event->venue->name }}</span
                                            >
                                        </div>
                                    @endisset
                                </div>
                                <p>{{ $event->preview }}</p>
                            </div>
                        </div>
                    </div>
                </x-portlet>
            </div>
        </div>
    </x-content>
</x-layouts.app>
