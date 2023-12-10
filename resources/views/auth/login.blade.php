<x-layouts.auth>
    <!--begin::Wrapper-->
    <x-auth-validation-errors :errors="$errors" />
    <div class="p-10 mx-auto rounded shadow-sm w-lg-500px bg-body p-lg-15">
        <!--begin::Form-->
        <form class="form w-100 fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate" id="kt_sign_in_form" action="{{ route('login') }}" method="post">
            @csrf
            <!--begin::Heading-->
            <div class="mb-10 text-center">
                <!--begin::Title-->
                <h1 class="mb-3 text-dark">Sign In to Ringside</h1>
                <!--end::Title-->
            </div>
            <!--begin::Heading-->
            <!--begin::Input group-->
            <div class="mb-10 fv-row fv-plugins-icon-container">
                <!--begin::Label-->
                <label class="form-label fs-6 fw-bolder text-dark">Email</label>
                <!--end::Label-->
                <!--begin::Input-->
                <input class="form-control form-control-lg form-control-solid" type="text" name="email" autocomplete="off">
                <!--end::Input-->
            <div class="fv-plugins-message-container invalid-feedback"></div></div>
            <!--end::Input group-->
            <!--begin::Input group-->
            <div class="mb-10 fv-row fv-plugins-icon-container">
                <!--begin::Wrapper-->
                <div class="mb-2 d-flex flex-stack">
                    <!--begin::Label-->
                    <label class="mb-0 form-label fw-bolder text-dark fs-6">Password</label>
                    <!--end::Label-->
                </div>
                <!--end::Wrapper-->
                <!--begin::Input-->
                <input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off">
                <!--end::Input-->
            <div class="fv-plugins-message-container invalid-feedback"></div></div>
            <!--end::Input group-->
            <!--begin::Actions-->
            <div class="text-center">
                <!--begin::Submit button-->
                <button type="submit" id="kt_sign_in_submit" class="mb-5 btn btn-lg btn-primary w-100">Continue</button>
                <!--end::Submit button-->
            </div>
            <!--end::Actions-->
        <div></div></form>
        <!--end::Form-->
    </div>
    <!--end::Wrapper-->
</x-layouts.auth>
