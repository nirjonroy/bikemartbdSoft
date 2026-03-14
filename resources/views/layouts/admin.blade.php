<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'BikeMart POS'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
            crossorigin="anonymous"
        >
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
            crossorigin="anonymous"
        >
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
            crossorigin="anonymous"
        >
        <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">

        <style>
            .brand-logo-preview,
            .settings-logo-preview {
                object-fit: cover;
            }

            .brand-logo-preview {
                width: 33px;
                height: 33px;
            }

            .settings-logo-preview {
                width: 96px;
                height: 96px;
            }

            .brand-logo-fallback,
            .settings-logo-fallback {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-weight: 700;
                color: #fff;
                background: linear-gradient(135deg, #0d6efd, #198754);
            }

            .brand-logo-fallback {
                width: 33px;
                height: 33px;
                font-size: 0.8rem;
            }

            .settings-logo-fallback {
                width: 96px;
                height: 96px;
                font-size: 2rem;
            }

            .app-main {
                min-height: calc(100vh - 114px);
            }
        </style>

        @stack('styles')
    </head>
    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
        @php
            $businessSetting = $businessSetting ?? null;
            $brandName = $businessSetting?->display_name ?? config('app.name', 'BikeMart POS');
            $hasCustomLogo = filled($businessSetting?->logo_path);
        @endphp

        <div class="app-wrapper">
            <nav class="app-header navbar navbar-expand bg-body">
                <div class="container-fluid">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                                <i class="bi bi-list"></i>
                            </a>
                        </li>
                        <li class="nav-item d-none d-md-block">
                            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        </li>
                        <li class="nav-item d-none d-md-block">
                            <a href="{{ route('business-settings.edit') }}" class="nav-link">Business Info</a>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item me-2 d-none d-md-block">
                            <span class="nav-link text-secondary">{{ now()->format('d M Y, h:i A') }}</span>
                        </li>
                        <li class="nav-item dropdown user-menu">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <img
                                    src="{{ asset('adminlte/assets/img/avatar.png') }}"
                                    class="user-image rounded-circle shadow"
                                    alt="User Avatar"
                                >
                                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                                <li class="user-header text-bg-primary">
                                    <img
                                        src="{{ asset('adminlte/assets/img/avatar.png') }}"
                                        class="rounded-circle shadow"
                                        alt="User Avatar"
                                    >
                                    <p>
                                        {{ auth()->user()->name }}
                                        <small>{{ auth()->user()->email }}</small>
                                    </p>
                                </li>
                                <li class="user-footer">
                                    <a href="{{ route('profile.edit') }}" class="btn btn-default btn-flat">Profile</a>
                                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-default btn-flat float-end">Sign out</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
                <div class="sidebar-brand">
                    <a href="{{ route('dashboard') }}" class="brand-link">
                        @if ($hasCustomLogo)
                            <img
                                src="{{ $businessSetting->logo_url }}"
                                alt="{{ $brandName }}"
                                class="brand-image brand-logo-preview opacity-75 shadow"
                            >
                        @else
                            <span class="brand-image brand-logo-fallback shadow">{{ $businessSetting?->initials ?? 'BM' }}</span>
                        @endif
                        <span class="brand-text fw-light">{{ $brandName }}</span>
                    </a>
                </div>

                <div class="sidebar-wrapper">
                    <nav class="mt-2">
                        <ul
                            class="nav sidebar-menu flex-column"
                            data-lte-toggle="treeview"
                            role="navigation"
                            aria-label="Main navigation"
                            data-accordion="false"
                        >
                            <li class="nav-header">OPERATIONS</li>
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-speedometer2"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('business-settings.edit') }}" class="nav-link {{ request()->routeIs('business-settings.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-building-gear"></i>
                                    <p>Business Information</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-person-circle"></i>
                                    <p>Profile</p>
                                </a>
                            </li>

                            <li class="nav-header">BUSINESS SNAPSHOT</li>
                            <li class="nav-item px-3 py-2 text-body-secondary small">
                                <div class="mb-2">Primary email</div>
                                <div class="fw-semibold text-white">{{ $businessSetting?->email ?: 'Not set yet' }}</div>
                            </li>
                            <li class="nav-item px-3 py-2 text-body-secondary small">
                                <div class="mb-2">Phone</div>
                                <div class="fw-semibold text-white">{{ $businessSetting?->phone ?: 'Not set yet' }}</div>
                            </li>
                            <li class="nav-item px-3 py-2 text-body-secondary small">
                                <div class="mb-2">Currency / Timezone</div>
                                <div class="fw-semibold text-white">
                                    {{ $businessSetting?->currency_code ?: 'BDT' }} / {{ $businessSetting?->timezone ?: config('app.timezone') }}
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>

            <div class="app-main">
                <div class="app-content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h3 class="mb-0">@yield('page_title', 'Dashboard')</h3>
                            </div>
                            <div class="col-sm-6">
                                @hasSection('breadcrumbs')
                                    <ol class="breadcrumb float-sm-end">
                                        @yield('breadcrumbs')
                                    </ol>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <main class="app-content">
                    <div class="container-fluid">
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-2">Please fix the following issues:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>

            <footer class="app-footer">
                <div class="float-end d-none d-sm-inline">BikeMart POS</div>
                <strong>&copy; {{ now()->year }} {{ $brandName }}.</strong> All rights reserved.
            </footer>
        </div>

        <script
            src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
            crossorigin="anonymous"
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
            crossorigin="anonymous"
        ></script>
        <script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
        <script>
            const sidebarWrapper = document.querySelector('.sidebar-wrapper');

            if (sidebarWrapper && window.OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
                window.OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: 'os-theme-light',
                        autoHide: 'leave',
                        clickScroll: true,
                    },
                });
            }
        </script>

        @stack('scripts')
    </body>
</html>
