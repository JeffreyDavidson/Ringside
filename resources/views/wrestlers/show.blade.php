<x-layouts.app>
    <x-subheader :title="$wrestler->name">
        <x-slot name="actions">
            <a href="{{ route('wrestlers.index') }}" class="btn btn-label-brand btn-bold">
                Back To Wrestlers
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        @if($wrestler->isUnemployed())
            <div class="alert alert-warning" role="alert">
                <strong>Warning!</strong>&nbsp;This wrestler is not employed!
            </div>
        @endif
        <div class="kt-grid kt-grid--desktop kt-grid--ver kt-grid--ver-desktop kt-app">
            <div id="kt_profile_aside" class="kt-grid__item kt-app__toggle kt-app__aside">
                <x-portlet title="Biography" headBorder="false">
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
                                        <span class="kt-widget__username">{{ $wrestler->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="kt-widget__body">
                                <div class="kt-widget__content">
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Height:</span>
                                        <span class="kt-widget__data">{{ $wrestler->formatted_height }}</span>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Weight:</span>
                                        <span class="kt-widget__data">{{ $wrestler->weight }} lbs.</span>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Hometown:</span>
                                        <span class="kt-widget__data">{{ $wrestler->hometown }}</span>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Signature Move:</span>
                                        <span class="kt-widget__data">{{ $wrestler->signature_move }}</span>
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
