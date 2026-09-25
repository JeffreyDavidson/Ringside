<p class="text-ringside-muted relative min-h-8 px-3 pt-3 pb-1 text-xs font-normal">
    <span
        :aria-hidden="! expanded"
        data-test="sidebar-menu-heading-label"
        class="absolute start-3 top-3 transition-opacity duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] group-data-[collapsed=true]:opacity-0 motion-reduce:transition-none"
    >{{ $slot }}</span>
    <span
        :aria-hidden="expanded"
        data-test="sidebar-menu-heading-collapsed"
        class="absolute inset-x-0 top-3 text-center opacity-0 transition-opacity duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] group-data-[collapsed=true]:opacity-100 motion-reduce:transition-none"
    >…</span>
</p>
