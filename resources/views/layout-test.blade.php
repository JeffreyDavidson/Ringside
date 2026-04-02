<x-layouts.app>
    <x-slot:sidebar>
        <x-sidebar.menu-accordion title="Dashboards" icon="element-11" :open="true">
            <x-sidebar.menu-item href="#" :nested="true" :active="true">Light Sidebar</x-sidebar.menu-item>
            <x-sidebar.menu-item href="#" :nested="true">Dark Sidebar</x-sidebar.menu-item>
        </x-sidebar.menu-accordion>

        <x-sidebar.menu-heading>User</x-sidebar.menu-heading>

        <x-sidebar.menu-accordion title="Public Profile" icon="profile-circle" :open="true">
            <x-sidebar.menu-sub-accordion title="Profiles" :open="true">
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Default</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Creator</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Company</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">NFT</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">Blogger</x-sidebar.menu-item>
                <x-sidebar.menu-item href="#" :nested="true" :deep="true">CRM</x-sidebar.menu-item>
                <x-sidebar.menu-show-more :count="4">
                    <x-sidebar.menu-item href="#" :nested="true" :deep="true">Gamer</x-sidebar.menu-item>
                    <x-sidebar.menu-item href="#" :nested="true" :deep="true">Feeds</x-sidebar.menu-item>
                    <x-sidebar.menu-item href="#" :nested="true" :deep="true">Plain</x-sidebar.menu-item>
                    <x-sidebar.menu-item href="#" :nested="true" :deep="true">Modal</x-sidebar.menu-item>
                </x-sidebar.menu-show-more>
            </x-sidebar.menu-sub-accordion>
        </x-sidebar.menu-accordion>
    </x-slot:sidebar>

    <x-slot:header>
        <x-header>
            <x-slot:megaMenu>
                <x-header.mega-menu>
                    {{-- Simple Link Item --}}
                    <x-header.mega-menu-item href="/" :active="true">
                        Home
                    </x-header.mega-menu-item>

                    {{-- Profiles Dropdown (matching Metronic demo1 structure) --}}
                    <x-header.mega-menu-dropdown title="Profiles" width="lg-xl">
                        <div class="pt-4 pb-2 lg:p-7.5">
                            <div class="grid lg:grid-cols-2 gap-5 lg:gap-10">
                                {{-- Profiles Column --}}
                                <div class="flex flex-col">
                                    <x-header.mega-menu-sub-heading>Profiles</x-header.mega-menu-sub-heading>
                                    <div class="grid lg:grid-cols-2 lg:gap-5">
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#" icon="badge">Default</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="coffee">Creator</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="abstract-41">Company</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="bitcoin">NFT</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="message-text">Blogger</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="devices">CRM</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="ghost">Gamer</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#" icon="book">Feeds</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="files">Plain</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="mouse-square">Modal</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="financial-schedule" badge="Soon">Freelancer</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="technology-4" badge="Soon">Developer</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="users" badge="Soon">Team</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                </div>

                                {{-- Other Pages Column --}}
                                <div class="flex flex-col">
                                    <x-header.mega-menu-sub-heading>Other Pages</x-header.mega-menu-sub-heading>
                                    <div class="grid lg:grid-cols-2 lg:gap-5">
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#" icon="element-6">Projects - 3 Columns</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="element-4">Projects - 2 Columns</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="office-bag">Works</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="people">Teams</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="icon">Network</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="chart-line-up-2">Activity</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#" icon="element-11">Campaigns - Card</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="kanban">Campaigns - List</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="file-sheet">Empty Page</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="document" badge="Soon">Documents</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" icon="award" badge="Soon">Badges</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-col gap-1.5">
                                <div class="text-base font-semibold text-foreground leading-none">
                                    Ready to Get Started?
                                </div>
                                <div class="text-sm font-medium text-muted-foreground">
                                    Take your docs to the next level
                                </div>
                            </div>
                            <a class="inline-flex items-center justify-center shrink-0 gap-1.5 px-3 py-2 text-[13px] font-medium rounded-md shadow-sm transition-all" style="background-color: #09090b; color: white;" href="#">
                                Read Documentation
                            </a>
                        </x-slot:footer>
                    </x-header.mega-menu-dropdown>

                    {{-- My Account Dropdown (with sidebar layout) --}}
                    <x-header.mega-menu-dropdown title="My Account" width="full">
                        <div class="flex flex-col lg:flex-row gap-0 w-full">
                            {{-- Sidebar Section --}}
                            <div class="lg:w-[250px] mt-2 lg:mt-0 lg:border-e lg:border-e-border rounded-xl lg:rounded-l-xl lg:rounded-r-none shrink-0 px-3 py-4 lg:p-7.5 bg-muted/25">
                                <x-header.mega-menu-sub-heading>General Pages</x-header.mega-menu-sub-heading>
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#" icon="technology-2">Integrations</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="notification-1">Notifications</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="key">API Keys</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="eye">Appearance</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="user-tick">Invite a Friend</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="support">Activity</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="verify" badge="Soon">Brand</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                            </div>

                            {{-- Main Content --}}
                            <div class="pt-4 pb-2 lg:p-7.5 lg:pb-5 grow">
                                <div class="grid lg:grid-cols-5 gap-5">
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Account Home</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Get Started</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">User Profile</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Company Profile</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">With Sidebar</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Enterprise</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Plain</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Modal</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Billing</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Basic Billing</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Enterprise</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Plans</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Billing History</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" badge="Soon">Tax Info</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" badge="Soon">Invoices</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#" badge="Soon">Gateways</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Security</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Get Started</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Security Overview</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">IP Addresses</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Privacy Settings</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Device Management</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Backup & Recovery</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Current Sessions</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Security Log</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Members & Roles</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Teams Starter</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Teams</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Team Info</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Members Starter</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Team Members</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Import Members</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Roles</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Permissions - Toggler</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Permissions - Check</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Other Pages</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Integrations</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Notifications</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">API Keys</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Appearance</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Invite a Friend</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Activity</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-header.mega-menu-dropdown>

                    {{-- Network Dropdown --}}
                    <x-header.mega-menu-dropdown title="Network" width="md-lg">
                        <div class="flex flex-col lg:flex-row">
                            {{-- Sidebar --}}
                            <div class="flex flex-col gap-5 lg:w-[250px] mt-2 lg:mt-0 lg:border-r lg:border-r-border rounded-xl lg:rounded-none lg:rounded-tl-xl shrink-0 px-3 py-4 lg:p-7.5 bg-muted/25">
                                <x-header.mega-menu-sub-heading>General Pages</x-header.mega-menu-sub-heading>
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#" icon="flag">Get Started</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="users" badge="Soon">Colleagues</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="coffee">Donates</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="chart-line-up-2">Vacancies</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                            </div>
                            {{-- Main Content --}}
                            <div class="pt-4 pb-2 lg:p-7.5 lg:pb-5 grow">
                                <x-header.mega-menu-sub-heading>Network</x-header.mega-menu-sub-heading>
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#">User Cards</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Mini Cards</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Author</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">NFT</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Social</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Saas Users</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Store Clients</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#">Visitors</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                            </div>
                        </div>
                    </x-header.mega-menu-dropdown>

                    {{-- Store Dropdown --}}
                    <x-header.mega-menu-dropdown title="Store" width="md">
                        <div class="pt-4 pb-2 lg:p-7.5">
                            <x-header.mega-menu-sub-heading>Store - Client</x-header.mega-menu-sub-heading>
                            <div class="grid lg:grid-cols-2 lg:gap-5">
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#" icon="home">Home</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="grid">Search Results - Grid</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="tablet-text-up">Search Results - List</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="picture">Product Details</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="handcart">Shopping Cart</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="heart">Wishlist</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#" icon="subtitle">Checkout - Order Summary</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="delivery">Checkout - Shipping Info</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="wallet">Checkout - Payment Method</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="check-circle">Checkout - Order Placed</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="archive">My Orders</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="document">Order Receipt</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-col gap-1.5">
                                <div class="text-base font-semibold text-foreground leading-none">
                                    Ready to Get Started?
                                </div>
                                <div class="text-sm font-medium text-muted-foreground">
                                    Take your docs to the next level of Metronic
                                </div>
                            </div>
                            <a class="inline-flex items-center justify-center shrink-0 gap-1.5 px-3 py-2 text-[13px] font-medium rounded-md shadow-sm transition-all" style="background-color: #09090b; color: white;" href="#">
                                Read Documentation
                            </a>
                        </x-slot:footer>
                    </x-header.mega-menu-dropdown>

                    {{-- Authentication Dropdown --}}
                    <x-header.mega-menu-dropdown title="Authentication" width="lg">
                        <div class="flex flex-col lg:flex-row">
                            {{-- Main Content --}}
                            <div class="pt-4 pb-2 lg:p-7.5 lg:pb-5 grow">
                                <div class="grid lg:grid-cols-2 gap-5">
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Classic Layout</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Sign In</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Sign Up</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">2FA</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Check Email</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                        <span class="text-muted-foreground font-medium text-sm p-2.5 pt-3">Reset Password</span>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Enter Email</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Check Email</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Change Password</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Password is Changed</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                    <div class="flex flex-col">
                                        <x-header.mega-menu-sub-heading>Branded Layout</x-header.mega-menu-sub-heading>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Sign In</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Sign Up</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">2FA</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Check Email</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                        <span class="text-muted-foreground font-medium text-sm p-2.5 pt-3">Reset Password</span>
                                        <x-header.mega-menu-sub>
                                            <x-header.mega-menu-dropdown-item href="#">Enter Email</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Check Email</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Change Password</x-header.mega-menu-dropdown-item>
                                            <x-header.mega-menu-dropdown-item href="#">Password is Changed</x-header.mega-menu-dropdown-item>
                                        </x-header.mega-menu-sub>
                                    </div>
                                </div>
                            </div>
                            {{-- Sidebar --}}
                            <div class="lg:w-[260px] mb-4 lg:mb-0 lg:border-s lg:border-s-border rounded-xl lg:rounded-e-xl lg:rounded-l-none shrink-0 px-3 py-4 lg:p-7.5 bg-muted/25">
                                <x-header.mega-menu-sub-heading>Other Pages</x-header.mega-menu-sub-heading>
                                <x-header.mega-menu-sub>
                                    <x-header.mega-menu-dropdown-item href="#" icon="like-2">Welcome Message</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="shield-cross">Account Deactivated</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="message-question">Error 404</x-header.mega-menu-dropdown-item>
                                    <x-header.mega-menu-dropdown-item href="#" icon="information">Error 500</x-header.mega-menu-dropdown-item>
                                </x-header.mega-menu-sub>
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-col gap-1.5">
                                <div class="text-base font-semibold text-foreground leading-none">
                                    Ready to Get Started?
                                </div>
                                <div class="text-sm font-medium text-muted-foreground">
                                    Take your docs to the next level of Metronic
                                </div>
                            </div>
                            <a class="inline-flex items-center justify-center shrink-0 gap-1.5 px-3 py-2 text-[13px] font-medium rounded-md shadow-sm transition-all" style="background-color: #09090b; color: white;" href="#">
                                Read Documentation
                            </a>
                        </x-slot:footer>
                    </x-header.mega-menu-dropdown>

                    {{-- Help Dropdown --}}
                    <x-header.mega-menu-dropdown title="Help" width="xs">
                        <div class="py-2.5">
                            <x-header.mega-menu-sub>
                                <x-header.mega-menu-dropdown-item href="#" icon="coffee">
                                    Getting Started
                                </x-header.mega-menu-dropdown-item>

                                <x-header.mega-menu-dropdown-submenu icon="information">
                                    <x-slot:title>Support Forum</x-slot:title>
                                    <x-header.mega-menu-sub>
                                        <x-header.mega-menu-dropdown-item href="#" icon="questionnaire-tablet">
                                            All Questions
                                        </x-header.mega-menu-dropdown-item>
                                        <x-header.mega-menu-dropdown-item href="#" icon="star">
                                            Popular Questions
                                        </x-header.mega-menu-dropdown-item>
                                        <x-header.mega-menu-dropdown-item href="#" icon="message-question">
                                            Ask Question
                                        </x-header.mega-menu-dropdown-item>
                                    </x-header.mega-menu-sub>
                                </x-header.mega-menu-dropdown-submenu>

                                <x-header.mega-menu-dropdown-item href="#" icon="subtitle" icon-right="information-2">
                                    Licenses & FAQ
                                </x-header.mega-menu-dropdown-item>

                                <x-header.mega-menu-separator />

                                <x-header.mega-menu-dropdown-item href="#" icon="questionnaire-tablet">
                                    Documentation
                                </x-header.mega-menu-dropdown-item>
                                <x-header.mega-menu-dropdown-item href="#" icon="share">
                                    Contact Us
                                </x-header.mega-menu-dropdown-item>
                            </x-header.mega-menu-sub>
                        </div>
                    </x-header.mega-menu-dropdown>
                </x-header.mega-menu>
            </x-slot:megaMenu>

            <x-slot:topbar>
                <x-header.topbar>
                    <x-header.topbar-button
                        icon="magnifier"
                        label="Search"
                        @click="$dispatch('open-search')"
                    />

                    <x-header.topbar-button
                        icon="notification-status"
                        label="Notifications"
                        @click="$dispatch('open-notifications')"
                    />

                    <x-header.user-dropdown />
                </x-header.topbar>
            </x-slot:topbar>
        </x-header>
    </x-slot:header>

    {{-- Search Modal --}}
    <x-header.search-modal placeholder="Search..." />

    {{-- Notifications Drawer --}}
    <x-header.notifications-drawer>
        <x-header.notification-item
            icon="user-tick"
            icon-color="success"
            title="New wrestler signed"
            description="John Smith has been signed to a contract."
            time="5 minutes ago"
            :unread="true"
        />
        <x-header.notification-item
            icon="calendar"
            icon-color="primary"
            title="Event scheduled"
            description="WrestleMania 50 has been scheduled for April 2025."
            time="1 hour ago"
            :unread="true"
        />
        <x-header.notification-item
            icon="medal-star"
            icon-color="warning"
            title="Title change"
            description="The World Championship has changed hands."
            time="2 hours ago"
        />
    </x-header.notifications-drawer>

    <div>
        <h1 class="text-xl font-semibold">Header Components Test</h1>
        <p class="mt-2 text-muted-foreground">Testing the Metronic-style mega menu with topbar items.</p>

        <div class="mt-6 space-y-4">
            <div class="p-4 bg-muted rounded-lg">
                <h2 class="font-medium mb-3">Mega Menu Features</h2>
                <ul class="text-sm text-muted-foreground space-y-1">
                    <li>Hover to open dropdowns on desktop</li>
                    <li>Click to toggle on mobile</li>
                    <li>Multi-column layouts with section headings</li>
                    <li>Footer areas with CTAs</li>
                    <li>Sidebar layouts for complex menus</li>
                </ul>
            </div>

            <div class="p-4 bg-muted rounded-lg">
                <h2 class="font-medium mb-2">Keyboard Shortcuts</h2>
                <ul class="text-sm text-muted-foreground space-y-1">
                    <li><kbd class="px-2 py-1 bg-background rounded border">Cmd/Ctrl + K</kbd> - Open search</li>
                </ul>
            </div>

            <div class="p-4 bg-muted rounded-lg">
                <h2 class="font-medium mb-2">Test Actions</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="$dispatch('open-search')"
                        class="px-4 py-2 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                    >
                        Open Search Modal
                    </button>
                    <button
                        @click="$dispatch('open-notifications')"
                        class="px-4 py-2 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                    >
                        Open Notifications
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
