<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('admin-dashboard.title', 'Admin Dashboard'))</title>

    {{-- Package styles --}}
    <link rel="stylesheet" href="{{ asset('vendor/admin-dashboard/css/admin.css') }}">

    @stack('styles')
</head>
<body>
    <div id="admin-app">
        {{-- Navigation --}}
        <nav class="admin-nav">
            <div class="admin-nav__brand">
                <a href="{{ route('admin.dashboard') }}">
                    @adminTitle
                </a>
            </div>

            <div class="admin-nav__user">
                @auth
                    <span>{{ auth()->user()->name ?? auth()->user()->email }}</span>
                @endauth
            </div>
        </nav>

        {{-- Sidebar --}}
        <aside class="admin-sidebar">
            @yield('sidebar')
        </aside>

        {{-- Main Content --}}
        <main class="admin-content">
            @if(session('success'))
                <div class="admin-alert admin-alert--success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="admin-alert admin-alert--error">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="admin-alert admin-alert--error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Package scripts --}}
    <script src="{{ asset('vendor/admin-dashboard/js/admin.js') }}"></script>

    @stack('scripts')
</body>
</html>
