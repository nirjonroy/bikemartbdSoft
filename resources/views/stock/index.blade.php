@extends('layouts.admin')

@section('title', 'Stock Management | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Stock Management')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Stock Management</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $totalVehicles }}</h3>
                    <p>Vehicle Catalog</p>
                </div>
                <i class="small-box-icon bi bi-bicycle"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $totalPurchasedUnits }}</h3>
                    <p>Purchased Units</p>
                </div>
                <i class="small-box-icon bi bi-bag-check"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>{{ $totalSoldUnits }}</h3>
                    <p>Sold Units</p>
                </div>
                <i class="small-box-icon bi bi-cash-stack"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $availableStockUnits }}</h3>
                    <p>Available Stock</p>
                </div>
                <i class="small-box-icon bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-success h-100">
                <div class="card-header">
                    <h3 class="card-title">In Stock Products</h3>
                </div>
                <div class="card-body">
                    <div class="display-6 fw-semibold mb-2">{{ $inStockVehicleCount }}</div>
                    <p class="text-muted mb-0">
                        Products with at least one available unit in stock.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-warning h-100">
                <div class="card-header">
                    <h3 class="card-title">Out of Stock Products</h3>
                </div>
                <div class="card-body">
                    <div class="display-6 fw-semibold mb-2">{{ $outOfStockVehicleCount }}</div>
                    <p class="text-muted mb-0">
                        Products that were purchased before but now have zero available stock.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary h-100">
                <div class="card-header">
                    <h3 class="card-title">Not Purchased Yet</h3>
                </div>
                <div class="card-body">
                    <div class="display-6 fw-semibold mb-2">{{ $notPurchasedVehicleCount }}</div>
                    <p class="text-muted mb-0">
                        Products in the catalog that have not been purchased yet.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Inventory Overview</h3>
        </div>
        <div class="card-body border-bottom">
            <div class="alert alert-info mb-3">
                Every purchase adds the entered quantity to stock. Every sale removes the entered quantity from stock.
            </div>
            <form method="GET" action="{{ route('stock.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $search }}"
                            placeholder="Vehicle, code, registration, engine, brand or category"
                        >
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>
                        <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="px-3 py-2 border-bottom bg-light small text-muted">
                Showing {{ $filteredVehicleCount }} vehicle {{ \Illuminate\Support\Str::plural('record', $filteredVehicleCount) }}.
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Purchased</th>
                            <th>Sold</th>
                            <th>Available</th>
                            <th>Latest Purchase</th>
                            <th>Latest Sale</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $vehicle->display_name }}</div>
                                    <div class="small text-muted">
                                        {{ $vehicle->brand->name }} / {{ $vehicle->category->name }}
                                    </div>
                                    <div class="small text-muted">
                                        Registration: {{ $vehicle->registration_number ?: 'Not added' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $vehicle->stock_badge_class }}">{{ $vehicle->stock_status }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $vehicle->purchased_quantity }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $vehicle->sold_quantity }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold {{ $vehicle->available_stock_quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $vehicle->available_stock_quantity }}
                                    </span>
                                </td>
                                <td>
                                    @if ($vehicle->latestPurchase)
                                        <div class="fw-semibold">{{ $vehicle->latestPurchase->purchasing_date?->format('d M Y') }}</div>
                                        <div class="small text-muted">{{ $vehicle->latestPurchase->name }}</div>
                                        <div class="small text-muted">{{ number_format((float) $vehicle->latestPurchase->grand_total, 2) }}</div>
                                    @else
                                        <span class="text-muted">No purchase recorded</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vehicle->latestSell)
                                        <div class="fw-semibold">{{ $vehicle->latestSell->selling_date?->format('d M Y') }}</div>
                                        <div class="small text-muted">{{ $vehicle->latestSell->name }}</div>
                                        <div class="small text-muted">{{ number_format((float) $vehicle->latestSell->selling_price_to_customer, 2) }}</div>
                                    @else
                                        <span class="text-muted">No sale recorded</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        @can('manage purchases')
                                            <a href="{{ route('purchases.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-outline-primary">Purchase</a>
                                        @endcan
                                        @can('manage sales')
                                            @if ($vehicle->available_stock_quantity > 0)
                                                <a href="{{ route('sells.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-outline-success">Sell</a>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted mb-3">No vehicles match the current stock filters.</div>
                                    @can('manage vehicles')
                                        <a href="{{ route('vehicles.create') }}" class="btn btn-primary">Create vehicle</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($vehicles->hasPages())
            <div class="card-footer clearfix">
                {{ $vehicles->links() }}
            </div>
        @endif
    </div>
@endsection
