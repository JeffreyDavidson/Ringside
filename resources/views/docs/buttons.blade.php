<x-layouts.app>
    <x-container-fixed>
        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Default</h2>
        <div class="mb-12 lg:mb-16">
            <!-- Tabs -->
            <div x-data="{
                selectedId: null,
                init() {
                    // Set the first available tab on the page on page load.
                    this.$nextTick(() => this.select(this.$id('tab', 1)))
                },
                select(id) {
                    this.selectedId = id
                },
                isSelected(id) {
                    return this.selectedId === id
                },
                whichChild(el, parent) {
                    return Array.from(parent.children).indexOf(el) + 1
                }
            }" x-id="['tab']">
                <div class="flex justify-between items-center gap-5 mb-4">
                    <!-- Tab List -->
                    <x-button-tabs>
                        <li>
                            <x-button id="$id('tab', whichChild($el.parentElement, $refs.tablist))"
                                @click="select($el.id)" @mousedown.prevent @focus="select($el.id)" class="h-8"
                                isActive=isSelected($el.id) @click="select($el.id)">Preview</x-button>
                        </li>
                        <li>
                            <x-button id="$id('tab', whichChild($el.parentElement, $refs.tablist))"
                                @click="select($el.id)" @mousedown.prevent @focus="select($el.id)"
                                class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                                @click="select($el.id)">HTML</x-button>
                        </li>
                    </x-button-tabs>
                </div>
                <!-- Panels -->
                <div id="default_1" x-show="isSelected('tab-1')">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.light>Light</x-buttons.light>
                                    <x-buttons.secondary>Secondary</x-buttons.secondary>
                                    <x-buttons.primary>Primary</x-buttons.primary>
                                    <x-buttons.success>Success</x-buttons.success>
                                    <x-buttons.info>Info</x-buttons.info>
                                    <x-buttons.danger>Danger</x-buttons.danger>
                                    <x-buttons.warning>Warning</x-buttons.warning>
                                    <x-buttons.dark>Dark</x-buttons.dark>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="default_2" x-show="isSelected('tab-2')">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html" style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)"
                                    tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.light</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Light
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.light</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.secondary</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Secondary
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.secondary</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.primary</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Primary
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.primary</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.success</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Success
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.success</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.info</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Info
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.info</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.danger</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Danger
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.danger</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.warning</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Warning
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.warning</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>x-button.dark</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
 Dark
<span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>x-button.dark</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Outline</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary variant="outline">Primary</x-buttons.primary>
                                    <x-buttons.success variant="outline">Success</x-buttons.success>
                                    <x-buttons.info variant="outline">Info</x-buttons.info>
                                    <x-buttons.danger variant="outline">Danger</x-buttons.danger>
                                    <x-buttons.warning variant="outline">Warning</x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Clear</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.light variant="clear">Light</x-buttons.light>
                                    <x-buttons.primary variant="clear">Primary</x-buttons.primary>
                                    <x-buttons.success variant="clear">Success</x-buttons.success>
                                    <x-buttons.info variant="clear">Info</x-buttons.info>
                                    <x-buttons.danger variant="clear">Danger</x-buttons.danger>
                                    <x-buttons.warning variant="clear">Warning</x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">With Icon - Default </h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Primary
                                    </x-buttons.primary>
                                    <x-buttons.success>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Success
                                    </x-buttons.success>
                                    <x-buttons.info>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Info
                                    </x-buttons.info>
                                    <x-buttons.danger>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Danger
                                    </x-buttons.danger>
                                    <x-buttons.warning>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Warning
                                    </x-buttons.warning>
                                    <x-buttons.dark>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Dark
                                    </x-buttons.dark>
                                    <x-buttons.secondary>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Secondary
                                    </x-buttons.secondary>
                                    <x-buttons.light>
                                        <x-slot:icon>
                                            <i class="ki-outline ki-plus-squared"></i>
                                        </x-slot:icon>
                                        Light
                                    </x-buttons.light>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">With Icon - Outline </h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary variant="outline">
                                        Primary
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.primary>
                                    <x-buttons.success variant="outline">
                                        Success
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.success>
                                    <x-buttons.info variant="outline">
                                        Info
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.info>
                                    <x-buttons.danger variant="outline">
                                        Danger
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.danger>
                                    <x-buttons.warning variant="outline">
                                        Warning
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">With Icon - Clear </h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.light variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Light
                                    </x-buttons.light>
                                    <x-buttons.primary variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Primary
                                    </x-buttons.primary>
                                    <x-buttons.success variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Success
                                    </x-buttons.success>
                                    <x-buttons.info variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Info
                                    </x-buttons.info>
                                    <x-buttons.danger variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Danger
                                    </x-buttons.danger>
                                    <x-buttons.warning variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                        Warning
                                    </x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Icon Only - Default</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.primary>
                                    <x-buttons.success>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.success>
                                    <x-buttons.info>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.info>
                                    <x-buttons.danger>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.danger>
                                    <x-buttons.warning>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.warning>
                                    <x-buttons.dark>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.dark>
                                    <x-buttons.secondary>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.secondary>
                                    <x-buttons.light>
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.light>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Icon Only - Outline</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary variant="outline">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.primary>
                                    <x-buttons.success variant="outline">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.success>
                                    <x-buttons.info variant="outline">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.info>
                                    <x-buttons.danger variant="outline">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.danger>
                                    <x-buttons.warning variant="outline">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Icon Only - Clear</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.light variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.light>
                                    <x-buttons.primary variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.primary>
                                    <x-buttons.success variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.success>
                                    <x-buttons.info variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.info>
                                    <x-buttons.danger variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.danger>
                                    <x-buttons.warning variant="clear">
                                        <i class="ki-outline ki-plus-squared"></i>
                                    </x-buttons.warning>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i
                                        class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Disabled</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary disabled>Default</x-buttons.primary>
                                    <x-buttons.primary variant="outline" disabled>Outline</x-buttons.primary>
                                    <x-buttons.light disabled>Light</x-buttons.light>
                                    <x-buttons.secondary disabled>Secondary</x-buttons.secondary>
                                    <x-buttons.primary variant="clear" disabled>Clear</x-buttons.primary>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i
                                        class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Sizes</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.primary size="xs">Extra Small</x-buttons.primary>
                                    <x-buttons.primary size="sm">Small</x-buttons.primary>
                                    <x-buttons.primary>Default</x-buttons.primary>
                                    <x-buttons.primary size="lg">Large</x-buttons.primary>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i
                                        class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Link</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex items-center flex-wrap justify-center py-6 gap-4">
                                    <x-buttons.link size="sm">Small link</x-buttons.link>
                                    <x-buttons.link>Default link</x-buttons.link>
                                    <x-buttons.link size="lg">Large link</x-buttons.link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i
                                        class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="flex leading-none text-gray-900 font-semibold text-xl mb-4">Tabs</h2>
        <div class="mb-12 lg:mb-16">
            <div class="mb-4">
                A basic button examples with available color options.
            </div>
            <div>
                <div class="flex justify-between items-center gap-5 mb-4">
                    <x-button-tabs>
                        <x-button class="h-8" tabToggle="default_1" isActive>Preview</x-button>
                        <x-button
                            class="h-8 hover:bg-light hover:border hover:border-solid hover:border-gray-200 hover:text-gray-900 hover:shadow-light"
                            tabToggle="default_2">HTML</x-button>
                    </x-button-tabs>
                </div>
                <div class="" id="default_1">
                    <div class="group" id="default_preview">
                        <div
                            class="flex flex-col rounded-xl border border-solid border-gray-200 shadow-none light:bg-[#fefefe] group-[.light]:!bg-[#fefefe] group-[.dark]:!bg-coal-300">
                            <div class="grow ps-[1.875rem] pe-[1.875rem] p-7">
                                <div class="flex flex-col items-center justify-center py-6 gap-8">
                                    <x-button-tabs size="sm">
                                        <x-buttons.icon isActive>
                                            <i class="ki-outline ki-element-11"></i>
                                        </x-buttons.icon>
                                        <x-buttons.icon>
                                            <i class="ki-outline ki-row-horizontal"></i>
                                        </x-buttons.icon>
                                    </x-button-tabs>

                                    <x-button-tabs>
                                        <x-buttons.icon isActive>
                                            <i class="ki-outline ki-element-11"></i>
                                        </x-buttons.icon>
                                        <x-buttons.icon>
                                            <i class="ki-outline ki-row-horizontal"></i>
                                        </x-buttons.icon>
                                    </x-button-tabs>

                                    <x-button-tabs size="lg">
                                        <x-buttons.icon isActive>
                                            <i class="ki-outline ki-element-11"></i>
                                        </x-buttons.icon>
                                        <x-buttons.icon>
                                            <i class="ki-outline ki-row-horizontal"></i>
                                        </x-buttons.icon>
                                    </x-button-tabs>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden" id="default_2">
                    <div class="grid">
                        <div class="min-w-full">
                            <div class="card-rounded relative group bg-coal-100 !py-1 !px-3">
                                <button
                                    class="group group-hover:flex hidden absolute top-0 right-0 btn btn-icon h-8 btn-icon-xl mt-3 mr-4 z-1"
                                    data-clipboard="true">
                                    <i
                                        class="ki-outline ki-copy text-white hover:text-primary group-[.copied]:hidden">
                                    </i>
                                    <i
                                        class="ki-outline ki-copy-success text-white hover:text-primary group-[.copied]:flex hidden">
                                    </i>
                                </button>
                                <pre class="!p-1 !bg-transparent scrollable-auto language-html"
                                    style="; --tw-scrollbar-thumb-color: var(--tw-gray-600)" tabindex="0"><code class="!font-normal !text-[13px] !bg-transparent !p-0 language-html"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-light<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Light
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-secondary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Secondary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-primary<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Primary
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-success<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Success
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-info<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Info
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-danger<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Danger
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-warning<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Warning
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span>
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;</span>button</span> <span class="font-normal attr-name">class</span><span class="font-normal attr-value"><span class="font-normal text-[#f472b6] attr-equals">=</span><span class="font-normal text-[#f472b6]">"</span>btn btn-dark<span class="font-normal text-[#f472b6]">"</span></span><span class="font-normal text-[#f472b6]">&gt;</span></span>
            Dark
           <span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]"><span class="font-normal text-[#f472b6]">&lt;/</span>button</span><span class="font-normal text-[#f472b6]">&gt;</span></span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </x-container-fluid>
</x-layouts.app>
