@extends('layouts.admin')

@section('title', 'Dashboard | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@php
    $currencyCode = $businessSetting->currency_code ?: 'BDT';
    $money = fn (float $amount) => $currencyCode . ' ' . number_format($amount, 2);
    $showStockAlerts = filled($stockAlertChart);
    $dailyMaxAbs = max(array_map(fn ($value) => abs((float) $value), $dailyProfitLossChart['values'])) ?: 1;
    $weeklyMaxAbs = max(array_map(fn ($value) => abs((float) $value), $weeklyProfitLossChart['values'])) ?: 1;
    $trendingMax = max($trendingItemsChart['values'] ?: [0]) ?: 1;
    $stockMax = $showStockAlerts ? max($stockAlertChart['values'] ?: [0]) ?: 1 : 1;
@endphp

@push('styles')
    <style>
        .analytics-hero {
            border: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.18), transparent 32%),
                linear-gradient(135deg, #f8fbff 0%, #ffffff 45%, #f7fff9 100%);
        }

        .analytics-hero::before {
            content: "";
            position: absolute;
            inset: auto -10% -35% auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(25, 135, 84, 0.08);
        }

        .analytics-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.09);
            color: #0d6efd;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .analytics-title {
            font-size: clamp(1.7rem, 2vw, 2.35rem);
            font-weight: 700;
            margin: 1rem 0 0.45rem;
        }

        .analytics-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
        }

        .analytics-chip {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(13, 110, 253, 0.08);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .analytics-chip span {
            display: block;
            color: #6c757d;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .analytics-chip strong {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .chart-card .card-header {
            align-items: flex-start;
            gap: 1rem;
        }

        .chart-title-wrap .chart-kicker {
            color: #6c757d;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.2rem;
        }

        .chart-subtitle {
            color: #6c757d;
            font-size: 0.92rem;
            margin: 0;
        }

        .balance-chart {
            display: flex;
            gap: 0.65rem;
            align-items: flex-end;
            min-height: 270px;
            padding-top: 0.5rem;
            overflow-x: auto;
        }

        .balance-column {
            min-width: 52px;
            flex: 1 0 0;
        }

        .balance-value {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 0.55rem;
            min-height: 2.1rem;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .balance-value.is-positive {
            color: #198754;
        }

        .balance-value.is-negative {
            color: #dc3545;
        }

        .balance-track {
            position: relative;
            height: 210px;
            border-radius: 1rem;
            background:
                linear-gradient(180deg, rgba(25, 135, 84, 0.04), transparent 42%),
                linear-gradient(0deg, rgba(220, 53, 69, 0.05), transparent 42%),
                #f8fafc;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .balance-track::after {
            content: "";
            position: absolute;
            left: 8px;
            right: 8px;
            top: 50%;
            border-top: 1px dashed rgba(108, 117, 125, 0.45);
        }

        .balance-bar {
            position: absolute;
            left: 22%;
            width: 56%;
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
        }

        .balance-bar.is-positive {
            bottom: 50%;
            border-radius: 14px 14px 6px 6px;
            background: linear-gradient(180deg, #34d399, #198754);
        }

        .balance-bar.is-negative {
            top: 50%;
            border-radius: 6px 6px 14px 14px;
            background: linear-gradient(180deg, #f87171, #dc3545);
        }

        .balance-label {
            text-align: center;
            color: #6c757d;
            font-size: 0.74rem;
            font-weight: 600;
            margin-top: 0.7rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.85rem;
        }

        .stats-tile {
            padding: 0.9rem 1rem;
            border-radius: 0.95rem;
            background: #f8fafc;
            border: 1px solid #eef2f7;
        }

        .stats-tile span {
            display: block;
            color: #6c757d;
            font-size: 0.78rem;
            margin-bottom: 0.35rem;
        }

        .stats-tile strong {
            font-size: 1rem;
            font-weight: 700;
        }

        .signal-stack {
            display: grid;
            gap: 1rem;
        }

        .signal-row {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fafcff;
            border: 1px solid #edf2f7;
        }

        .signal-meter {
            height: 0.75rem;
            border-radius: 999px;
            background: #edf2f7;
            overflow: hidden;
        }

        .signal-fill {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .signal-fill.is-primary {
            background: linear-gradient(90deg, #0d6efd, #4dabf7);
        }

        .signal-fill.is-danger {
            background: linear-gradient(90deg, #dc3545, #f87171);
        }

        .signal-fill.is-warning {
            background: linear-gradient(90deg, #fd7e14, #ffc107);
        }

        .signal-fill.is-success {
            background: linear-gradient(90deg, #198754, #34d399);
        }

        .utility-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
        }

        .action-grid .btn {
            padding-block: 0.8rem;
        }

        .dashboard-note {
            color: #6c757d;
            font-size: 0.82rem;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $staffCount }}</h3>
                    <p>Staff Accounts</p>
                </div>
                <i class="small-box-icon bi bi-people-fill"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $brandCount }}</h3>
                    <p>Brands</p>
                </div>
                <i class="small-box-icon bi bi-bookmark-star"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $categoryCount }}</h3>
                    <p>Categories</p>
                </div>
                <i class="small-box-icon bi bi-diagram-3"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $vehicleCount }}</h3>
                    <p>Vehicles / Products</p>
                </div>
                <i class="small-box-icon bi bi-bicycle"></i>
            </div>
        </div>
    </div>

    <div class="card analytics-hero shadow-sm position-relative mb-4">
        <div class="card-body position-relative">
            <div class="row g-4 align-items-center">
                <div class="col-xl-5">
                    <span class="analytics-eyebrow">
                        <i class="bi bi-activity"></i>
                        Performance cockpit
                    </span>
                    <h2 class="analytics-title">{{ $businessSetting->display_name }}</h2>
                    <p class="text-muted mb-0">
                        Daily and weekly performance signals for <strong>{{ $locationScopeLabel }}</strong>.
                        The profit views use estimated cost of sales based on recorded purchase costs.
                    </p>
                </div>
                <div class="col-xl-7">
                    <div class="analytics-summary-grid">
                        <div class="analytics-chip">
                            <span>Today Profit / Loss</span>
                            <strong class="{{ $todayProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($todayProfitLoss) }}</strong>
                        </div>
                        <div class="analytics-chip">
                            <span>This Week Profit / Loss</span>
                            <strong class="{{ $currentWeekProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($currentWeekProfitLoss) }}</strong>
                        </div>
                        @if ($showStockAlerts)
                            <div class="analytics-chip">
                                <span>Open Stock Alerts</span>
                                <strong class="{{ $openStockAlerts > 0 ? 'text-danger' : 'text-success' }}">{{ $openStockAlerts }}</strong>
                            </div>
                        @else
                            <div class="analytics-chip">
                                <span>Purchase Records</span>
                                <strong>{{ $purchaseCount }}</strong>
                            </div>
                        @endif
                        <div class="analytics-chip">
                            <span>Top Trending Item</span>
                            <strong>{{ $topTrendingItem }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-outline card-primary chart-card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="chart-title-wrap">
                        <div class="chart-kicker">Last {{ count($dailyProfitLossChart['labels']) }} Days</div>
                        <h3 class="card-title">Daily Profit / Loss</h3>
                        <p class="chart-subtitle">Estimated daily margin trend from sales against average purchase cost.</p>
                    </div>
                    @can('view reports')
                        <a href="{{ route('reports.profit-loss') }}" class="btn btn-sm btn-outline-primary">Full Report</a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="balance-chart">
                        @foreach ($dailyProfitLossChart['labels'] as $index => $label)
                            @php
                                $value = (float) $dailyProfitLossChart['values'][$index];
                                $barHeight = max((int) round((abs($value) / $dailyMaxAbs) * 88), $value == 0.0 ? 0 : 8);
                            @endphp
                            <div class="balance-column">
                                <div class="balance-value {{ $value >= 0 ? 'is-positive' : 'is-negative' }}">
                                    {{ $value == 0.0 ? '0' : number_format($value, 0) }}
                                </div>
                                <div class="balance-track">
                                    @if ($value !== 0.0)
                                        <div
                                            class="balance-bar {{ $value >= 0 ? 'is-positive' : 'is-negative' }}"
                                            style="height: {{ $barHeight }}px;"
                                        ></div>
                                    @endif
                                </div>
                                <div class="balance-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <div class="stats-grid">
                        <div class="stats-tile">
                            <span>14-Day Net</span>
                            <strong class="{{ $dailyProfitLossChart['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $money($dailyProfitLossChart['net_profit']) }}
                            </strong>
                        </div>
                        <div class="stats-tile">
                            <span>Best Day</span>
                            <strong class="text-success">{{ $money($dailyProfitLossChart['best_day_profit']) }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Worst Day</span>
                            <strong class="text-danger">{{ $money($dailyProfitLossChart['worst_day_profit']) }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Positive vs Negative</span>
                            <strong>{{ $dailyProfitLossChart['positive_days'] }} / {{ $dailyProfitLossChart['negative_days'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-outline card-success chart-card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="chart-title-wrap">
                        <div class="chart-kicker">Last {{ count($weeklyProfitLossChart['labels']) }} Weeks</div>
                        <h3 class="card-title">Weekly Profit / Loss</h3>
                        <p class="chart-subtitle">Weekly trend to spot momentum shifts faster than the detailed reports.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="balance-chart">
                        @foreach ($weeklyProfitLossChart['labels'] as $index => $label)
                            @php
                                $value = (float) $weeklyProfitLossChart['values'][$index];
                                $barHeight = max((int) round((abs($value) / $weeklyMaxAbs) * 88), $value == 0.0 ? 0 : 8);
                            @endphp
                            <div class="balance-column">
                                <div class="balance-value {{ $value >= 0 ? 'is-positive' : 'is-negative' }}">
                                    {{ $value == 0.0 ? '0' : number_format($value, 0) }}
                                </div>
                                <div class="balance-track">
                                    @if ($value !== 0.0)
                                        <div
                                            class="balance-bar {{ $value >= 0 ? 'is-positive' : 'is-negative' }}"
                                            style="height: {{ $barHeight }}px;"
                                        ></div>
                                    @endif
                                </div>
                                <div class="balance-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <div class="stats-grid">
                        <div class="stats-tile">
                            <span>This Week</span>
                            <strong class="{{ $weeklyProfitLossChart['current_week_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $money($weeklyProfitLossChart['current_week_profit']) }}
                            </strong>
                        </div>
                        <div class="stats-tile">
                            <span>8-Week Net</span>
                            <strong class="{{ $weeklyProfitLossChart['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $money($weeklyProfitLossChart['net_profit']) }}
                            </strong>
                        </div>
                        <div class="stats-tile">
                            <span>Strongest Week</span>
                            <strong class="text-success">{{ $money($weeklyProfitLossChart['strongest_week_profit']) }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Weakest Week</span>
                            <strong class="text-danger">{{ $money($weeklyProfitLossChart['weakest_week_profit']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1">
        <div class="{{ $showStockAlerts ? 'col-xl-7' : 'col-12' }}">
            <div class="card card-outline card-info chart-card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="chart-title-wrap">
                        <div class="chart-kicker">Last {{ 30 }} Days</div>
                        <h3 class="card-title">Trending Items</h3>
                        <p class="chart-subtitle">Top-performing vehicles/products based on sold quantity.</p>
                    </div>
                    @can('view reports')
                        <a href="{{ route('reports.trending-products') }}" class="btn btn-sm btn-outline-info">View Ranking</a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="signal-stack">
                        @forelse ($trendingItemsChart['items'] as $item)
                            @php
                                $width = max((int) round(($item['sold_quantity'] / $trendingMax) * 100), $item['sold_quantity'] > 0 ? 10 : 0);
                            @endphp
                            <div class="signal-row">
                                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $item['label'] }}</div>
                                        <div class="small text-muted">{{ $item['subtitle'] }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">{{ $item['sold_quantity'] }} sold</div>
                                        <div class="small text-muted">{{ $money($item['revenue']) }}</div>
                                    </div>
                                </div>
                                <div class="signal-meter">
                                    <span class="signal-fill is-primary" style="width: {{ $width }}%"></span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">No sale data is available yet to determine trending items.</div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer">
                    <div class="stats-grid">
                        <div class="stats-tile">
                            <span>Top Item</span>
                            <strong>{{ $trendingItemsChart['top_item'] }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Sold Units in Chart</span>
                            <strong>{{ $trendingItemsChart['total_units'] }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Scope</span>
                            <strong>{{ $locationScopeLabel }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($showStockAlerts)
            <div class="col-xl-5">
                <div class="card card-outline card-danger chart-card h-100">
                    <div class="card-header d-flex justify-content-between">
                        <div class="chart-title-wrap">
                            <div class="chart-kicker">Inventory Warning Panel</div>
                            <h3 class="card-title">Stock Alerts</h3>
                            <p class="chart-subtitle">Out-of-stock, critical, and low-stock pressure in the selected branch scope.</p>
                        </div>
                        @can('manage stock')
                            <a href="{{ route('stock.index') }}" class="btn btn-sm btn-outline-danger">Open Stock</a>
                        @endcan
                    </div>
                    <div class="card-body">
                        <div class="signal-stack mb-4">
                            @foreach ($stockAlertChart['labels'] as $index => $label)
                                @php
                                    $value = (int) $stockAlertChart['values'][$index];
                                    $width = max((int) round(($value / $stockMax) * 100), $value > 0 ? 12 : 0);
                                    $fillClass = match ($label) {
                                        'Out of Stock' => 'is-danger',
                                        'Critical' => 'is-danger',
                                        'Low' => 'is-warning',
                                        default => 'is-success',
                                    };
                                @endphp
                                <div class="signal-row">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold">{{ $label }}</div>
                                        <div class="small text-muted">{{ $value }} item(s)</div>
                                    </div>
                                    <div class="signal-meter">
                                        <span class="signal-fill {{ $fillClass }}" style="width: {{ $width }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="dashboard-note fw-semibold mb-2">Top alert items</div>
                        <div class="signal-stack">
                            @forelse ($stockAlertChart['items'] as $item)
                                <div class="signal-row">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $item['label'] }}</div>
                                            <div class="small text-muted">{{ $item['subtitle'] }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold">{{ $item['available_stock'] }} left</div>
                                            <div class="small text-muted">{{ $item['status'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No stock alerts are open right now.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="stats-grid">
                            <div class="stats-tile">
                                <span>Open Alerts</span>
                                <strong class="{{ $stockAlertChart['open_alerts'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $stockAlertChart['open_alerts'] }}
                                </strong>
                            </div>
                            <div class="stats-tile">
                                <span>Healthy Items</span>
                                <strong class="text-success">{{ $stockAlertChart['healthy_items'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row mt-1">
        <div class="col-xl-7">
            <div class="card card-outline card-success utility-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="action-grid">
                        <a href="{{ route('vehicles.create') }}" class="btn btn-outline-success">
                            <i class="bi bi-bicycle me-1"></i>
                            Add Vehicle
                        </a>
                        <a href="{{ route('purchases.create') }}" class="btn btn-outline-primary">
                            <i class="bi bi-bag-check me-1"></i>
                            Record Purchase
                        </a>
                        <a href="{{ route('sells.create') }}" class="btn btn-outline-success">
                            <i class="bi bi-cash-stack me-1"></i>
                            Record Sale
                        </a>
                        @can('manage stock')
                            @if ($businessSetting->show_stock_management_module ?? true)
                                <a href="{{ route('stock.index') }}" class="btn btn-outline-dark">
                                    <i class="bi bi-box-seam me-1"></i>
                                    Stock Management
                                </a>
                            @endif
                        @endcan
                        <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-bookmark-star me-1"></i>
                            Manage Brands
                        </a>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-diagram-3 me-1"></i>
                            Manage Categories
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">Business Pulse</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stats-tile">
                            <span>Branch Scope</span>
                            <strong>{{ $locationScopeLabel }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Purchases</span>
                            <strong>{{ $purchaseCount }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Sales</span>
                            <strong>{{ $saleCount }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Profile Completion</span>
                            <strong>{{ $profileCompletion }}%</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Email</span>
                            <strong>{{ $businessSetting->email ?: 'Not set' }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Phone</span>
                            <strong>{{ $businessSetting->phone ?: 'Not set' }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Website</span>
                            <strong>{{ $businessSetting->website ?: 'Not set' }}</strong>
                        </div>
                        <div class="stats-tile">
                            <span>Currency / Timezone</span>
                            <strong>{{ $currencyCode }} / {{ $businessSetting->timezone ?: config('app.timezone') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="dashboard-note">{{ $businessSetting->invoice_footer ?: 'Add invoice footer text from Business Information when needed.' }}</span>
                    <a href="{{ route('business-settings.edit') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-square me-1"></i>
                        Update Business Information
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
