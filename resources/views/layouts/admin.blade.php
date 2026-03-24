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
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-center">
                        @if (auth()->check() && $accessibleLocations->isNotEmpty())
                            <li class="nav-item me-3">
                                <form method="POST" action="{{ route('locations.switch') }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    <label for="active_location_id" class="small text-muted mb-0 d-none d-md-inline">Location</label>
                                    <select
                                        id="active_location_id"
                                        name="location_id"
                                        class="form-select form-select-sm"
                                        onchange="this.form.submit()"
                                        style="min-width: 220px;"
                                    >
                                        @foreach ($accessibleLocations as $locationOption)
                                            <option value="{{ $locationOption->id }}" @selected($activeLocation && $activeLocation->id === $locationOption->id)>
                                                {{ $locationOption->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </li>
                        @endif

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
                            @can('manage brands')
                                <li class="nav-item">
                                    <a href="{{ route('brands.index') }}" class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bookmark-star"></i>
                                        <p>Brands</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage locations')
                                <li class="nav-item">
                                    <a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-geo-alt"></i>
                                        <p>Locations</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage categories')
                                <li class="nav-item">
                                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-diagram-3"></i>
                                        <p>Categories</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage vehicles')
                                <li class="nav-item">
                                    <a href="{{ route('vehicles.index') }}" class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bicycle"></i>
                                        <p>Vehicles</p>
                                    </a>
                                </li>
                            @endcan

                            <li class="nav-header">OPERATIONS</li>
                            @can('view dashboard')
                                <li class="nav-item">
                                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-speedometer2"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage stock')
                                <li class="nav-item">
                                    <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-box-seam"></i>
                                        <p>Stock Management</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage purchases')
                                <li class="nav-item">
                                    <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bag-check"></i>
                                        <p>Purchases</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage sales')
                                <li class="nav-item">
                                    <a href="{{ route('sells.index') }}" class="nav-link {{ request()->routeIs('sells.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-cash-stack"></i>
                                        <p>Sales</p>
                                    </a>
                                </li>
                            @endcan
                            @can('view reports')
                                <li class="nav-item {{ request()->routeIs('reports.*') ? 'menu-open' : '' }}">
                                    <a href="#" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-bar-chart-line"></i>
                                        <p>
                                            Reports
                                            <i class="nav-arrow bi bi-chevron-right"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="{{ route('reports.profit-loss') }}" class="nav-link {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Profit / Loss Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.purchase-sale') }}" class="nav-link {{ request()->routeIs('reports.purchase-sale') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Purchase &amp; Sale</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.supplier-customer') }}" class="nav-link {{ request()->routeIs('reports.supplier-customer') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Supplier &amp; Customer Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.stock') }}" class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Stock Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.trending-products') }}" class="nav-link {{ request()->routeIs('reports.trending-products') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Trending Products</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.items') }}" class="nav-link {{ request()->routeIs('reports.items') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Items Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.product-purchase') }}" class="nav-link {{ request()->routeIs('reports.product-purchase') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Product Purchase Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.product-sell') }}" class="nav-link {{ request()->routeIs('reports.product-sell') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Product Sell Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.purchase-payment') }}" class="nav-link {{ request()->routeIs('reports.purchase-payment') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Purchase Payment Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.sell-payment') }}" class="nav-link {{ request()->routeIs('reports.sell-payment') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Sell Payment Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.expense') }}" class="nav-link {{ request()->routeIs('reports.expense') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Expense Report</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('reports.activity-log') }}" class="nav-link {{ request()->routeIs('reports.activity-log') ? 'active' : '' }}">
                                                <i class="nav-icon bi bi-arrow-right-short"></i>
                                                <p>Activity Log</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endcan
                            @can('manage business settings')
                                <li class="nav-item">
                                    <a href="{{ route('business-settings.edit') }}" class="nav-link {{ request()->routeIs('business-settings.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-building-gear"></i>
                                        <p>Business Information</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage users')
                                <li class="nav-item">
                                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-people"></i>
                                        <p>Users</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage roles')
                                <li class="nav-item">
                                    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-person-badge"></i>
                                        <p>Roles</p>
                                    </a>
                                </li>
                            @endcan
                            @can('manage permissions')
                                <li class="nav-item">
                                    <a href="{{ route('permissions.index') }}" class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-shield-lock"></i>
                                        <p>Permissions</p>
                                    </a>
                                </li>
                            @endcan
                            <li class="nav-item">
                                <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-person-circle"></i>
                                    <p>Profile</p>
                                </a>
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
                            @php
                                $statusMessage = match (session('status')) {
                                    'profile-updated' => 'Profile updated successfully.',
                                    'password-updated' => 'Password updated successfully.',
                                    'verification-link-sent' => 'A new verification link has been sent to your email address.',
                                    default => session('status'),
                                };
                            @endphp
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ $statusMessage }}
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
