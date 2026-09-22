<p class="text-ringside-muted min-h-8 px-3 pt-3 pb-1 text-xs font-normal transition-opacity duration-300 ease-out group-data-[collapsed=true]:text-center">
    <span x-show="expanded">{{ $slot }}</span>
    <span x-show="! expanded" aria-hidden="true">…</span>
</p>
