@extends('layouts.admin')

@section('title', 'Sales | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Sales')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Sales</li>
@endsection

@section('content')
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Sales Records</h3>
            <a href="{{ route('sells.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>New Sale
            </a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('sells.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $search }}"
                            placeholder="Customer, mobile, vehicle, registration"
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
                        <label for="date_from" class="form-label">Sale From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-lg-3">
                        <label for="date_to" class="form-label">Sale To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ route('sells.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="px-3 py-2 border-bottom bg-light small text-muted">
                Showing {{ $sells->total() }} sale {{ \Illuminate\Support\Str::plural('record', $sells->total()) }}.
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Selling Date</th>
                            <th>Selling Price</th>
                            <th>Picture</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sells as $sell)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $sell->vehicle?->display_name ?: 'Not linked' }}</div>
                                    <div class="small text-muted">
                                        {{ $sell->vehicle?->brand?->name ?: 'No brand' }} / {{ $sell->vehicle?->category?->name ?: 'No category' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $sell->name }}</div>
                                    <div class="small text-muted">{{ $sell->mobile_number ?: 'Mobile not added' }}</div>
                                </td>
                                <td>{{ $sell->quantity }}</td>
                                <td>{{ $sell->selling_date?->format('d M Y') }}</td>
                                <td>{{ number_format((float) $sell->selling_price_to_customer, 2) }}</td>
                                <td>{{ $sell->pictures_count ? 'Uploaded' : 'No' }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('sells.show', $sell) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('sells.edit', $sell) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('sells.destroy', $sell) }}" onsubmit="return confirm('Delete this sale record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        {{ $hasFilters ? 'No sale records match the current filters.' : 'No sale records found.' }}
                                    </div>
                                    <a href="{{ route('sells.create') }}" class="btn btn-success">Create the first sale</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($sells->hasPages())
            <div class="card-footer clearfix">
                {{ $sells->links() }}
            </div>
        @endif
    </div>
@endsection
