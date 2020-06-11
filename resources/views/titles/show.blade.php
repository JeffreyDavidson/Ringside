<x-layouts.app>
    <x-subheader :title="$title->name">
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
        </div>
    </x-content>
</x-layouts.app>
