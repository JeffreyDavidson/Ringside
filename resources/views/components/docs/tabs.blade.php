@props(['defaultTab' => 'preview'])

<div x-data="{ activeTab: '{{ $defaultTab }}' }">
    <div class="flex justify-between items-center gap-5 mb-4">
        <div class="inline-flex items-center leading-none bg-gray-100 border border-solid border-gray-200 rounded-md h-10 p-1 gap-1">
            <button 
                @click="activeTab = 'preview'"
                :class="{ 'bg-white text-gray-900 shadow-sm': activeTab === 'preview', 'text-gray-600 hover:text-gray-900': activeTab !== 'preview' }"
                class="inline-flex items-center cursor-pointer leading-none rounded-md outline-none h-8 px-2.5 text-sm font-medium transition-all duration-200"
                type="button">
                Preview
            </button>
            <button 
                @click="activeTab = 'html'"
                :class="{ 'bg-white text-gray-900 shadow-sm': activeTab === 'html', 'text-gray-600 hover:text-gray-900': activeTab !== 'html' }"
                class="inline-flex items-center cursor-pointer leading-none rounded-md outline-none h-8 px-2.5 text-sm font-medium transition-all duration-200"
                type="button">
                HTML
            </button>
        </div>
    </div>
    
    <div x-show="activeTab === 'preview'" x-transition>
        {{ $preview }}
    </div>
    
    <div x-show="activeTab === 'html'" x-transition class="hidden">
        {{ $html }}
    </div>
</div>