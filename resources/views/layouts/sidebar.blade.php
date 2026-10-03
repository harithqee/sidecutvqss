@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();
@endphp

<aside id="sidebar"
    class="fixed left-0 top-0 z-99999 flex h-screen flex-col border-r border-gray-200 bg-white px-4 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900"
    :class="{
        'w-[264px]': $store.sidebar.isOpen,
        'w-[84px]': !$store.sidebar.isOpen,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <!-- Brand -->
    <a href="/" class="flex h-[72px] shrink-0 items-center gap-3 px-2"
        :class="$store.sidebar.isOpen ? 'justify-start' : 'xl:justify-center'">
        <img src="/images/logo/logo-icon.svg" alt="" width="32" height="32" class="shrink-0" />
        <span x-show="$store.sidebar.isOpen" class="leading-tight">
            <span class="block text-base font-semibold text-gray-900 dark:text-white">Sidecut</span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">Barbershop queue</span>
        </span>
    </a>

    <!-- Navigation -->
    <nav class="no-scrollbar -mx-1 flex-1 overflow-y-auto px-1 pb-4 pt-2" aria-label="Main">
        @foreach ($menuGroups as $menuGroup)
            <div class="mb-6 last:mb-0">
                <h2 class="mb-2 flex h-5 items-center px-3 text-[11px] font-medium uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500"
                    :class="$store.sidebar.isOpen ? 'justify-start' : 'xl:justify-center'">
                    <span x-show="$store.sidebar.isOpen">{{ $menuGroup['title'] }}</span>
                    <span x-show="!$store.sidebar.isOpen" class="h-px w-5 bg-gray-200 dark:bg-gray-800"></span>
                </h2>

                <ul class="flex flex-col gap-0.5">
                    @foreach ($menuGroup['items'] as $item)
                        @php
                            $isExternal = !empty($item['external']);
                            $isActive = !$isExternal && MenuHelper::isActive($item['path']);
                        @endphp
                        <li>
                            <a href="{{ $item['path'] }}"
                                @if ($isExternal) target="_blank" rel="noopener" @endif
                                @if ($isActive) aria-current="page" @endif
                                title="{{ $item['name'] }}"
                                class="menu-item group {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                :class="$store.sidebar.isOpen ? 'justify-start' : 'xl:justify-center'">
                                <span class="[&_svg]:h-5 [&_svg]:w-5 {{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                    {!! MenuHelper::getIconSvg($item['icon']) !!}
                                </span>
                                <span x-show="$store.sidebar.isOpen" class="menu-item-text flex-1">{{ $item['name'] }}</span>
                                @if ($isExternal)
                                    <svg x-show="$store.sidebar.isOpen" class="text-gray-400 dark:text-gray-500" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                                    <span class="sr-only">(opens in a new tab)</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <!-- Live shop status -->
    <div x-show="$store.sidebar.isOpen" class="shrink-0 pb-5">
        @include('layouts.sidebar-widget')
    </div>
</aside>

<!-- Mobile overlay -->
<div x-show="$store.sidebar.isMobileOpen" x-cloak @click="$store.sidebar.setMobileOpen(false)"
    class="fixed inset-0 z-9999 bg-gray-900/50 xl:hidden"></div>
