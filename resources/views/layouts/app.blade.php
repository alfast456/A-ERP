<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | A-ERP Manufacturing</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            500: '#16a34a',
                            600: '#15803d',
                            700: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-600 text-white font-extrabold text-lg shadow-sm">A</span>
                <div>
                    <a href="{{ route('dashboard') }}" class="font-bold text-lg tracking-tight text-white hover:text-emerald-400">A-ERP</a>
                    <span class="text-xs text-slate-400 block -mt-1">Knitting Plant Edition</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center space-x-1 text-xs">
                <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('orders.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('orders.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Orders
                </a>
                <a href="{{ route('production.monitoring') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('production.monitoring') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Sisa Produksi
                </a>
                <a href="{{ route('machines.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('machines.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Mesin (3.036)
                </a>
                <a href="{{ route('quality.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('quality.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Kualitas & BS
                </a>
                <a href="{{ route('downtimes.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('downtimes.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Downtime
                </a>
                <a href="{{ route('maintenance.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('maintenance.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Servis Tiket
                </a>
                <a href="{{ route('yarn.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('yarn.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Benang
                </a>
                <a href="{{ route('capacity.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('capacity.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Kapasitas
                </a>
                <a href="{{ route('audit.index') }}" class="px-2.5 py-1.5 rounded-md font-medium transition {{ request()->routeIs('audit.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Audit
                </a>
            </nav>

            <!-- Status Indicator -->
            <div class="flex items-center space-x-2 text-xs text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>DB: <strong class="text-white">a_erp</strong> (6.7M Rows)</span>
            </div>
        </div>
    </header>

    <!-- Sub-navigation for mobile / small screen -->
    <div class="lg:hidden bg-slate-800 text-slate-200 border-b border-slate-700 overflow-x-auto text-xs py-2 px-4 flex items-center space-x-3 whitespace-nowrap">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-emerald-400 font-bold' : '' }}">Dashboard</a>
        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'text-emerald-400 font-bold' : '' }}">Orders</a>
        <a href="{{ route('production.monitoring') }}" class="{{ request()->routeIs('production.monitoring') ? 'text-emerald-400 font-bold' : '' }}">Sisa Produksi</a>
        <a href="{{ route('machines.index') }}" class="{{ request()->routeIs('machines.*') ? 'text-emerald-400 font-bold' : '' }}">Mesin</a>
        <a href="{{ route('quality.index') }}" class="{{ request()->routeIs('quality.*') ? 'text-emerald-400 font-bold' : '' }}">Kualitas</a>
        <a href="{{ route('downtimes.index') }}" class="{{ request()->routeIs('downtimes.*') ? 'text-emerald-400 font-bold' : '' }}">Downtime</a>
        <a href="{{ route('maintenance.index') }}" class="{{ request()->routeIs('maintenance.*') ? 'text-emerald-400 font-bold' : '' }}">Servis</a>
        <a href="{{ route('yarn.index') }}" class="{{ request()->routeIs('yarn.*') ? 'text-emerald-400 font-bold' : '' }}">Benang</a>
        <a href="{{ route('capacity.index') }}" class="{{ request()->routeIs('capacity.*') ? 'text-emerald-400 font-bold' : '' }}">Kapasitas</a>
        <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'text-emerald-400 font-bold' : '' }}">Audit</a>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-md shadow-sm">
                <div class="flex items-center">
                    <span class="text-emerald-500 font-bold mr-2">✓</span>
                    <p class="text-sm text-emerald-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-md shadow-sm">
                <div class="flex items-start">
                    <span class="text-rose-500 font-bold mr-2">✕</span>
                    <ul class="text-sm text-rose-700 font-medium list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-4 text-center text-xs text-slate-500">
        <p>A-ERP (Enterprise Manufacturing System) — Laravel 12.x | PHP 8.2 | MariaDB 10.11</p>
    </footer>

</body>
</html>
