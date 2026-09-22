<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') !== 'light' }"
      :class="{ 'dark': darkMode }" class="transition-theme">
<head>
    <script>
        (() => { try { if (localStorage.getItem('theme') !== 'light') document.documentElement.classList.add('dark'); } catch (error) {} })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MediForm') — MediForm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-theme-bg text-theme-text transition-theme app-body"
      x-data="{ sidebarOpen: window.innerWidth > 1024 }">
    @include('components.site-loader')

    <div class="app-shell flex">
        <!-- Sidebar -->
        <aside class="app-sidebar bg-sidebar flex-shrink-0 flex flex-col transition-all duration-300"
               :class="sidebarOpen ? 'w-56 app-sidebar-open' : 'w-0 overflow-hidden app-sidebar-closed'">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="w-8 h-8 bg-theme-primary rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-heart-pulse text-white text-sm"></i>
                </div>
                <span class="text-white font-bold text-lg tracking-tight">MediForm</span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto">
                <p class="text-xs font-semibold text-white/30 uppercase tracking-widest px-3 mb-2">Main</p>
                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high icon"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.doctors.index') }}"
                   class="sidebar-item {{ request()->routeIs('admin.doctors*') ? 'active' : '' }}">
                    <i class="fas fa-user-doctor icon"></i>
                    <span>Doctors</span>
                </a>

                <p class="text-xs font-semibold text-white/30 uppercase tracking-widest px-3 mt-4 mb-2">Forms</p>
                <a href="{{ route('admin.forms.index') }}"
                   class="sidebar-item {{ request()->routeIs('admin.forms.index') || (request()->routeIs('admin.forms*') && !request()->routeIs('admin.forms.create')) ? 'active' : '' }}">
                    <i class="fas fa-list-check icon"></i>
                    <span>All Forms</span>
                </a>
                <a href="{{ route('admin.forms.create') }}"
                   class="sidebar-item {{ request()->routeIs('admin.forms.create') ? 'active' : '' }}">
                    <i class="fas fa-circle-plus icon"></i>
                    <span>Create Form</span>
                </a>

                <p class="text-xs font-semibold text-white/30 uppercase tracking-widest px-3 mt-4 mb-2">Data</p>
                <a href="{{ route('admin.responses.index') }}"
                   class="sidebar-item {{ request()->routeIs('admin.responses*') ? 'active' : '' }}">
                    <i class="fas fa-inbox icon"></i>
                    <span>Responses</span>
                </a>
            </nav>

            <!-- User section -->
            <div class="px-3 py-3 border-t border-white/10">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg">
                    <div class="w-8 h-8 rounded-full bg-theme-primary flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xs font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-white/40 text-xs">Super Admin</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mt-1">
                    @csrf
                    <button type="submit" class="sidebar-item w-full text-left">
                        <i class="fas fa-arrow-right-from-bracket icon"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="app-sidebar-backdrop" style="display:none"></div>

        <!-- Main content -->
        <div class="app-main-content flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="app-topbar bg-theme-surface border-b border-theme flex items-center gap-4 px-6 py-3 sticky top-0 z-40">
                <button @click="sidebarOpen = !sidebarOpen"
                        class="text-theme-muted hover:text-theme-text transition-colors">
                    <i class="fas fa-bars text-lg"></i>
                </button>

                <div class="flex-1">
                    <h1 class="text-base font-semibold text-theme-text">@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                            class="btn btn-ghost btn-sm rounded-full w-9 h-9 p-0"
                            :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <!-- User dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-3 py-1.5 hover:bg-theme-surface-2 transition-colors">
                            <div class="w-7 h-7 rounded-full bg-theme-primary flex items-center justify-center">
                                <span class="text-white text-xs font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </div>
                            <span class="text-sm font-medium text-theme-text hidden sm:block">{{ auth()->user()->name }}</span>
                            <i class="fas fa-chevron-down text-xs text-theme-muted"></i>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute right-0 top-full mt-1 w-44 card py-1 z-50" style="display:none">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-theme-danger hover:bg-theme-surface-2 transition-colors flex items-center gap-2">
                                    <i class="fas fa-arrow-right-from-bracket"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash messages -->
            @if(session('success') || session('error') || session('warning'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)" x-transition.opacity
                 class="fixed top-4 right-4 z-50" style="display:none">
                @if(session('success'))
                <div class="toast border-l-4" style="border-left-color: rgb(var(--color-success))">
                    <i class="fas fa-check-circle text-theme-success mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm font-medium text-theme-text flex-1">{{ session('success') }}</p>
                    <button @click="show = false" class="text-theme-muted hover:text-theme-text flex-shrink-0"><i class="fas fa-times"></i></button>
                </div>
                @endif
                @if(session('error'))
                <div class="toast border-l-4" style="border-left-color: rgb(var(--color-danger))">
                    <i class="fas fa-times-circle text-theme-danger mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm font-medium text-theme-text flex-1">{{ session('error') }}</p>
                    <button @click="show = false" class="text-theme-muted hover:text-theme-text flex-shrink-0"><i class="fas fa-times"></i></button>
                </div>
                @endif
                @if(session('warning'))
                <div class="toast border-l-4" style="border-left-color: rgb(var(--color-warning))">
                    <i class="fas fa-exclamation-triangle text-theme-warning mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm font-medium text-theme-text flex-1">{{ session('warning') }}</p>
                    <button @click="show = false" class="text-theme-muted hover:text-theme-text flex-shrink-0"><i class="fas fa-times"></i></button>
                </div>
                @endif
            </div>
            @endif

            <!-- Page content -->
            <main class="app-main flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</body>
</html>
