<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="h-full bg-gray-50 dark:bg-gray-900">

<head>
    <script>
        (function () {
            if (localStorage.getItem('dir') === 'rtl') document.documentElement.setAttribute('dir', 'rtl');
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.head-meta')

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <!-- Theme & Sidebar Alpine Store Initialization -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    this.theme = savedTheme === 'dark' ? 'dark' : 'light';
                    this.updateTheme();
                },
                theme: 'light',
                resolvedTheme: 'light',
                set(value) {
                    value = value === 'dark' ? 'dark' : 'light';
                    this.theme = value;
                    localStorage.setItem('theme', value);
                    this.updateTheme();
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: value }));
                },
                toggle() {
                    this.set(this.resolvedTheme === 'dark' ? 'light' : 'dark');
                },
                updateTheme() {
                    const html = document.documentElement;
                    const isDark = this.theme === 'dark';
                    if (isDark) {
                        html.classList.add('dark');
                    } else {
                        html.classList.remove('dark');
                    }

                    this.resolvedTheme = isDark ? 'dark' : 'light';
                    html.setAttribute('data-color-scheme', this.resolvedTheme);
                    html.dataset['theme'] = this.resolvedTheme;
                    html.style.colorScheme = this.resolvedTheme;
                    if (document.body) {
                        document.body.dataset['theme'] = this.resolvedTheme;
                        document.body.style.colorScheme = this.resolvedTheme;
                    }
                }
            });

            Alpine.store('sidebar', {
                isExpanded: true,
                isMobileOpen: false,
                isHovered: false,

                init() {
                    const savedState = localStorage.getItem('sidebarExpanded');
                    if (window.innerWidth >= 1280) {
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    } else {
                        this.isExpanded = false;
                    }
                    this.isMobileOpen = false;

                    window.addEventListener('resize', () => {
                        this.handleResize();
                    });
                },

                handleResize() {
                    if (window.innerWidth < 1280) {
                        if (this.isMobileOpen) {
                             this.isMobileOpen = false;
                        }
                    } else {
                        this.isMobileOpen = false;
                        const savedState = localStorage.getItem('sidebarExpanded');
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    }
                },

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                    if (window.innerWidth >= 1280) {
                        localStorage.setItem('sidebarExpanded', this.isExpanded);
                    }
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Prevent flash of unstyled theme -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const isDark = savedTheme === 'dark';
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-color-scheme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-color-scheme', 'light');
            }
        })();
    </script>
</head>

<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 font-sans antialiased">

    <div class="min-h-screen xl:flex sidebar-expanded" x-data :class="{ 'sidebar-expanded': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen }">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 ml-0 ltr:xl:ml-[90px] rtl:xl:ml-0 rtl:xl:mr-[90px] [.sidebar-expanded_&]:ltr:xl:ml-[290px] [.sidebar-expanded_&]:rtl:xl:ml-0 [.sidebar-expanded_&]:rtl:xl:mr-[290px] transition-all duration-300 ease-in-out flex flex-col min-h-screen">
            <!-- App Header -->
            @include('layouts.app-header')

            <!-- Main Page Content -->
            <main class="flex-1 p-4 mx-auto w-full max-w-(--breakpoint-2xl) md:p-6">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-300 shadow-xs" role="alert">
                        <span class="flex size-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 text-sm font-bold">✓</span>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-rose-800 dark:border-rose-800/40 dark:bg-rose-950/30 dark:text-rose-300 shadow-xs" role="alert">
                        <div class="flex items-center gap-2 font-semibold text-sm mb-1.5">
                            <span class="flex size-6 items-center justify-center rounded-md bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300 text-xs font-bold">✕</span>
                            <span>Terdapat kendala input:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-xs pl-8 font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-gray-200 bg-white py-3.5 px-6 text-center text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                <p>A-ERP Enterprise Manufacturing System — Laravel 12.x | Tailwind CSS v4 | Alpine.js</p>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
