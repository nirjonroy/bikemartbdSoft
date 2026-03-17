@extends('layouts.admin')

@section('title', 'Vehicles | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Vehicles / Products')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Vehicles</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Vehicle Catalog</h3>
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Vehicle
            </a>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('vehicles.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $search }}"
                            placeholder="Vehicle, code, model, registration, engine"
                        >
                    </div>
                    <div class="col-lg-3">
                        <label for="brand_id" class="form-label">Brand</label>
                        <select id="brand_id" name="brand_id" class="form-select">
                            <option value="">All brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) $selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="px-3 py-2 border-bottom bg-light small text-muted">
                Showing {{ $vehicles->total() }} vehicle {{ \Illuminate\Support\Str::plural('record', $vehicles->total()) }}.
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Registration</th>
                            <th>Purchases</th>
                            <th>Sales</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $vehicle->display_name }}</div>
                                    <div class="small text-muted">
                                        {{ $vehicle->model ?: 'Model not added' }}
                                        @if ($vehicle->year)
                                            | {{ $vehicle->year }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $vehicle->brand->name }}</td>
                                <td>{{ $vehicle->category->name }}</td>
                                <td>{{ $vehicle->registration_number ?: 'Not added' }}</td>
                                <td>{{ $vehicle->purchases_count }}</td>
                                <td>{{ $vehicle->sells_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Delete this vehicle?');">
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
                                        {{ $hasFilters ? 'No vehicles match the current filters.' : 'No vehicles found.' }}
                                    </div>
                                    <a href="{{ route('vehicles.create') }}" class="btn btn-primary">Create the first vehicle</a>
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
