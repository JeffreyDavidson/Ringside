<div class="h-[70px] hidden items-center relative justify-between px-3 shrink-0 lg:flex lg:px-6">
    <a href="{{ route('dashboard') }}">
        <img class="min-h-[22px] max-w-none hidden lg:block" src="{{ Vite::image('app/default-logo.svg') }}" />
        <img class="min-h-[22px] max-w-none lg:hidden" src="{{ Vite::image('app/mini-logo.svg') }}" />
    </a>
    <button
        class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 bg-light text-gray-500 hover:text-gray-700 toggle absolute left-full top-2/4 -translate-x-2/4 -translate-y-2/4">
        <i class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300"></i>
    </button>
    <button
        class="btn btn-icon btn-icon-md size-[30px] rounded-lg border border-gray-200 bg-light text-gray-500 hover:text-gray-700 toggle absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4">
        <i class="ki-filled ki-black-left-line toggle-active:rotate-180 transition-all duration-300"></i>
    </button>
</div>
