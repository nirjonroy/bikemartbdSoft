@extends('layouts.admin')

@section('title', 'Purchases | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Purchases')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Purchases</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Purchase Records</h3>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Purchase
            </a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('purchases.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $search }}"
                            placeholder="Owner, mobile, vehicle, registration"
                        >
                    </div>
                    <div class="col-lg-4">
                        <label for="vehicle_id" class="form-label">Vehicle / Product</label>
                        <select id="vehicle_id" name="vehicle_id" class="form-select">
                            <option value="">All vehicles</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string) $selectedVehicleId === (string) $vehicle->id)>
                                    {{ $vehicle->display_name }} | {{ $vehicle->brand->name }} / {{ $vehicle->category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label for="brand_id" class="form-label">Brand</label>
                        <select id="brand_id" name="brand_id" class="form-select">
                            <option value="">All brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) $selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="date_from" class="form-label">Purchase From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-lg-3">
                        <label for="date_to" class="form-label">Purchase To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="px-3 py-2 border-bottom bg-light small text-muted">
                Showing {{ $purchases->total() }} purchase {{ \Illuminate\Support\Str::plural('record', $purchases->total()) }}.
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Purchase Date</th>
                            <th>Buying Price</th>
                            <th>Modifying Cost</th>
                            <th>Pictures</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $purchase->vehicle?->display_name ?: 'Not linked' }}</div>
                                    <div class="small text-muted">
                                        {{ $purchase->vehicle?->brand?->name ?: 'No brand' }} / {{ $purchase->vehicle?->category?->name ?: 'No category' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $purchase->name }}</div>
                                    <div class="small text-muted">{{ $purchase->mobile_number ?: 'Mobile not added' }}</div>
                                </td>
                                <td>{{ $purchase->quantity }}</td>
                                <td>{{ $purchase->purchasing_date?->format('d M Y') }}</td>
                                <td>{{ number_format((float) $purchase->buying_price_from_owner, 2) }}</td>
                                <td>{{ number_format((float) ($purchase->modifying_costs_sum ?? 0), 2) }}</td>
                                <td>{{ $purchase->pictures_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        {{ $hasFilters ? 'No purchase records match the current filters.' : 'No purchase records found.' }}
                                    </div>
                                    <a href="{{ route('purchases.create') }}" class="btn btn-primary">Create the first purchase</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($purchases->hasPages())
            <div class="card-footer clearfix">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
@endsection
