<x-layouts.auth>
    <form class="flex flex-col gap-5 p-10" method="post" action="{{ route('login') }}">
        @csrf
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                Sign in
            </h3>
        </div>
        <div class="flex flex-col gap-1">
            <label class="flex w-full text-2sm font-normal text-gray-900">Email</label>
            <input class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-[#fcfcfc] rounded-md h-10 ps-3 pe-3 border border-solid border-gray-300 text-gray-700" placeholder="email@email.com" type="text" value="{{ old('email') }}" name="email">
            @error('email')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>
        <div class="flex flex-col gap-1">
            <label class="flex w-full text-2sm font-normal text-gray-900">Password</label>
            <input name="password" placeholder="Enter Password" type="password" value="" class="block w-full appearance-none shadow-none outline-none font-medium text-2sm leading-4 bg-[#fcfcfc] rounded-md h-10 ps-3 pe-3 border border-solid border-gray-300 text-gray-700" value="{{ old('password') }}">
            @error('password')
                <span class="font-medium text-xs leading-4 text-red-500">
                    {{ $message }}
                </span>
            @enderror
        </div>
        <button class="flex items-center cursor-pointer leading-4 rounded-md h-10 ps-px pe-px gap-1.5 border border-solid border-transparent font-medium text-2sm outline-none text-white bg-primary justify-center grow hover:bg-primary-active hover:shadow-[0_4px_12px_0px_rgba(40,132,239,0.35)]">Sign In</button>
    </form>
</x-layouts.auth>
