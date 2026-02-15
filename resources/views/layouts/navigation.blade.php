<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex flex-1">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 hover:scale-[1.15] transition ease-in-out duration-[225ms]" :text="false"/>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:-my-px grow md:ms-10 md:flex">

                    @canany(['publish_content', 'edit_content', 'delete_content', 'manage_archived', 'manage_content_types', 'manage_tags'])
                    <x-nav-link :href="route('content.index')" :active="request()->routeIs('content.*')">
                        {{ $contentNavTitle ?? __('Content') }}
                    </x-nav-link>
                    {{ $contentNav ?? '' }}
                    @endcanany

                    @can('manage_screens')
                    <x-nav-link :href="route('screens.index')" :active="request()->routeIs('screens.*')">
                        {{ $screensNavTitle ?? __('Screens') }}
                    </x-nav-link>
                    {{ $screensNav ?? '' }}
                    @endcan

                    @canany(['create_profiles', 'edit_profiles', 'delete_profiles'])
                    <x-nav-link :href="route('profiles.index')" :active="request()->routeIs('profiles.*')">
                        {{ $profilesNavTitle ?? __('Profiles') }}
                    </x-nav-link>
                    {{ $profilesNav ?? '' }}
                    @endcanany

                    @canany(['delete_modules', 'edit_modules', 'create_modules', 'manage_module_categories'])
                    <x-nav-link :href="route('modules.index')" :active="request()->routeIs('modules.*')">
                        {{ $modulesNavTitle ?? __('Modules') }}
                    </x-nav-link>
                    {{ $modulesNav ?? '' }}
                    @endcanany

                    @canany(['manage_acl', 'manage_users'])
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ $usersNavTitle ?? __('Users') }}
                    </x-nav-link>
                    {{ $usersNav ?? '' }}
                    @endcanany

                    @can('manage_acl')
                    <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                        {{ $rolesNavTitle ?? __('Roles') }}
                    </x-nav-link>
                    {{ $rolesNav ?? '' }}
                    @endcan

                    <div id="nav-item-spacer" class="grow-[5]"></div>

                    @can('view_events')
                    <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                        {{ __('Events') }}
                    </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden md:flex md:items-center md:justify-items-end md:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('settings.edit')">
                            {{ __('Settings') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @canany(['publish_content', 'edit_content', 'delete_content', 'manage_archived', 'manage_content_types', 'manage_tags'])
            <x-responsive-nav-link :href="route('content.index')" :active="request()->routeIs('content.*')">
                {{ $contentNavTitle ?? __('Content') }}
            </x-responsive-nav-link>
            @endcanany

            @can('manage_screens')
            <x-responsive-nav-link :href="route('screens.index')" :active="request()->routeIs('screens.*')">
                {{ $screensNavTitle ?? __('Screens') }}
            </x-responsive-nav-link>
            @endcan

            @canany(['create_profiles', 'edit_profiles', 'delete_profiles'])
            <x-responsive-nav-link :href="route('profiles.index')" :active="request()->routeIs('profiles.*')">
                {{ $profilesNavTitle ?? __('Profiles') }}
            </x-responsive-nav-link>
            @endcanany

            @canany(['delete_modules', 'edit_modules', 'create_modules', 'manage_module_categories'])
            <x-responsive-nav-link :href="route('modules.index')" :active="request()->routeIs('modules.*')">
                {{ $modulesNavTitle ?? __('Modules') }}
            </x-responsive-nav-link>
            @endcanany

            @canany(['manage_acl', 'manage_users'])
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                {{ $usersNavTitle ?? __('Users') }}
            </x-responsive-nav-link>
            @endcanany

            @can('manage_acl')
            <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                {{ $rolesNavTitle ?? __('Roles') }}
            </x-responsive-nav-link>
            @endcan

            @can('view_events')
            <x-responsive-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                {{ __('Events') }}
            </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('settings.edit')">
                    {{ __('Settings') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
