<nav class="flex flex-col grow" data-menu="true">
    {{-- ========================================
         SECTION 1: No Heading
         ======================================== --}}

    {{-- Dashboards --}}
    <x-sidebar.menu-accordion title="Dashboards" icon="element-11" :open="true">
        <x-sidebar.menu-item href="#" :nested="true" :active="true">Light Sidebar</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Dark Sidebar</x-sidebar.menu-item>
    </x-sidebar.menu-accordion>

    {{-- ========================================
         SECTION 2: User Heading
         ======================================== --}}

    <x-sidebar.menu-heading>User</x-sidebar.menu-heading>

    {{-- Public Profile --}}
    <x-sidebar.menu-accordion title="Public Profile" icon="profile-circle">
        {{-- Profiles Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Profiles">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Default</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Creator</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Company</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">NFT</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Blogger</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">CRM</x-sidebar.menu-item>
            <x-sidebar.menu-show-more :count="4" :deep="true">
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Gamer</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Feeds</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Plain</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Modal</x-sidebar.menu-item>
            </x-sidebar.menu-show-more>
        </x-sidebar.menu-sub-accordion>

        {{-- Projects Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Projects">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">3 Columns</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">2 Columns</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        <x-sidebar.menu-item href="#" :nested="true">Works</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Teams</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Network</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Activity</x-sidebar.menu-item>

        <x-sidebar.menu-show-more :count="3">
            <x-sidebar.menu-item href="#" :nested="true">Campaigns - Card</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true">Campaigns - List</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true">Empty</x-sidebar.menu-item>
        </x-sidebar.menu-show-more>
    </x-sidebar.menu-accordion>

    {{-- My Account --}}
    <x-sidebar.menu-accordion title="My Account" icon="setting-2">
        {{-- Account Home Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Account Home">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Get Started</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">User Profile</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Company Profile</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Settings - With Sidebar</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Settings - Enterprise</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Settings - Plain</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Settings - Modal</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        {{-- Billing Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Billing">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Billing - Basic</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Billing - Enterprise</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Plans</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Billing History</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        {{-- Security Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Security">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Get Started</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Security Overview</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Allowed IP Addresses</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Privacy Settings</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Device Management</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Backup & Recovery</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Current Sessions</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Security Log</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        {{-- Members & Roles Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Members & Roles">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Teams Starter</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Teams</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Team Info</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Members Starter</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Team Members</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Import Members</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Roles</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Permissions - Toggler</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Permissions - Check</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        <x-sidebar.menu-item href="#" :nested="true">Integrations</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Notifications</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">API Keys</x-sidebar.menu-item>

        <x-sidebar.menu-show-more :count="3">
            <x-sidebar.menu-item href="#" :nested="true">Appearance</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true">Invite a Friend</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true">Activity</x-sidebar.menu-item>
        </x-sidebar.menu-show-more>
    </x-sidebar.menu-accordion>

    {{-- Network --}}
    <x-sidebar.menu-accordion title="Network" icon="users">
        <x-sidebar.menu-item href="#" :nested="true">Get Started</x-sidebar.menu-item>

        {{-- User Cards Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="User Cards">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Mini Cards</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Team Crew</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Author</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">NFT</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Social</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        {{-- User Table Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="User Table">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Team Crew</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">App Roster</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Market Authors</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">SaaS Users</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Store Clients</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Visitors</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        <x-sidebar.menu-label :nested="true" badge="Soon">Cooperations</x-sidebar.menu-label>
        <x-sidebar.menu-label :nested="true" badge="Soon">Leads</x-sidebar.menu-label>
        <x-sidebar.menu-label :nested="true" badge="Soon">Donators</x-sidebar.menu-label>
    </x-sidebar.menu-accordion>

    {{-- Authentication --}}
    <x-sidebar.menu-accordion title="Authentication" icon="security-user">
        {{-- Classic Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Classic">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Sign In</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Sign Up</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">2FA</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Check Email</x-sidebar.menu-item>
            {{-- Reset Password Nested Sub-Accordion --}}
            <x-sidebar.menu-sub-accordion title="Reset Password">
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Enter Email</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Check Email</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Change Password</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Password is Changed</x-sidebar.menu-item>
            </x-sidebar.menu-sub-accordion>
        </x-sidebar.menu-sub-accordion>

        {{-- Branded Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Branded">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Sign In</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Sign Up</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">2FA</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Check Email</x-sidebar.menu-item>
            {{-- Reset Password Nested Sub-Accordion --}}
            <x-sidebar.menu-sub-accordion title="Reset Password">
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Enter Email</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Check Email</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Change Password</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Password is Changed</x-sidebar.menu-item>
            </x-sidebar.menu-sub-accordion>
        </x-sidebar.menu-sub-accordion>

        <x-sidebar.menu-item href="#" :nested="true">Welcome Message</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Account Deactivated</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Error 404</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Error 500</x-sidebar.menu-item>
    </x-sidebar.menu-accordion>

    {{-- ========================================
         SECTION 3: Apps Heading
         ======================================== --}}

    <x-sidebar.menu-heading>Apps</x-sidebar.menu-heading>

    {{-- Store - Client --}}
    <x-sidebar.menu-accordion title="Store - Client" icon="users">
        <x-sidebar.menu-item href="#" :nested="true">Home</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Search Results - Grid</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Search Results - List</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Product Details</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Shopping Cart</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Wishlist</x-sidebar.menu-item>

        {{-- Checkout Sub-Accordion --}}
        <x-sidebar.menu-sub-accordion title="Checkout">
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Order Summary</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Shipping Info</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Payment Method</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true" :deep="true">Order Placed</x-sidebar.menu-item>
        </x-sidebar.menu-sub-accordion>

        <x-sidebar.menu-item href="#" :nested="true">My Orders</x-sidebar.menu-item>
        <x-sidebar.menu-item href="#" :nested="true">Order Receipt</x-sidebar.menu-item>
    </x-sidebar.menu-accordion>

    {{-- Store - Admin (Soon) --}}
    <x-sidebar.menu-label icon="setting" badge="Soon">Store - Admin</x-sidebar.menu-label>

    {{-- Store - Services (Soon) --}}
    <x-sidebar.menu-label icon="python" badge="Soon">Store - Services</x-sidebar.menu-label>

    {{-- AI Prompt (Soon) --}}
    <x-sidebar.menu-label icon="artificial-intelligence" badge="Soon">AI Prompt</x-sidebar.menu-label>

    {{-- Invoice Generator (Soon) --}}
    <x-sidebar.menu-label icon="cheque" badge="Soon">Invoice Generator</x-sidebar.menu-label>
</nav>
