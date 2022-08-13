<x-layouts.app>
    <x-slot name="toolbar">
        <div id="kt_app_toolbar" class="py-3 app-toolbar py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                <!--begin::Page title-->
                <div class="flex-wrap page-title d-flex flex-column justify-content-center me-3">
                    <!--begin::Title-->
                    <h1 class="my-0 page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center">Tag Teams</h1>
                    <!--end::Title-->
                    <!--begin::Breadcrumb-->
                    <ul class="pt-1 my-0 breadcrumb breadcrumb-separatorless fw-semibold fs-7">
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bg-gray-400 bullet w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('tag-teams.index') }}" class="text-muted text-hover-primary">Tag Teams</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bg-gray-400 bullet w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('tag-teams.show', $tagTeam) }}" class="text-muted text-hover-primary">{{ $tagTeam->name }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bg-gray-400 bullet w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Edit</li>
                        <!--end::Item-->
                    </ul>
                    <!--end::Breadcrumb-->
                </div>
                <!--end::Page title-->
            </div>
            <!--end::Toolbar container-->
        </div>
    </x-slot>

</x-layouts.app>
