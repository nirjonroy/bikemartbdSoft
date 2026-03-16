@extends('layouts.admin')

@section('title', 'Vehicle Details | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Vehicle Details')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Vehicle Profile</h3>
                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">Edit Vehicle</a>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Vehicle</dt>
                        <dd class="col-sm-7">{{ $vehicle->display_name }}</dd>

                        <dt class="col-sm-5">Brand</dt>
                        <dd class="col-sm-7">{{ $vehicle->brand->name }}</dd>

                        <dt class="col-sm-5">Category</dt>
                        <dd class="col-sm-7">{{ $vehicle->category->name }}</dd>

                        <dt class="col-sm-5">Model</dt>
                        <dd class="col-sm-7">{{ $vehicle->model ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Year</dt>
                        <dd class="col-sm-7">{{ $vehicle->year ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Color</dt>
                        <dd class="col-sm-7">{{ $vehicle->color ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Registration</dt>
                        <dd class="col-sm-7">{{ $vehicle->registration_number ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Engine No.</dt>
                        <dd class="col-sm-7">{{ $vehicle->engine_number ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Chassis No.</dt>
                        <dd class="col-sm-7">{{ $vehicle->chassis_number ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('purchases.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-bag-check me-1"></i>Record Purchase
                    </a>
                    <a href="{{ route('sells.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-outline-success">
                        <i class="bi bi-cash-stack me-1"></i>Record Sale
                    </a>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Notes</h3>
                </div>
                <div class="card-body">
                    {{ $vehicle->notes ?: 'No notes added.' }}
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Purchase History</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Owner</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vehicle->purchases as $purchase)
                                    <tr>
                                        <td>{{ $purchase->name }}</td>
                                        <td>{{ $purchase->purchasing_date?->format('d M Y') }}</td>
                                        <td>{{ number_format((float) $purchase->buying_price_from_owner, 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No purchase history for this vehicle.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Sales History</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vehicle->sells as $sell)
                                    <tr>
                                        <td>{{ $sell->name }}</td>
                                        <td>{{ $sell->selling_date?->format('d M Y') }}</td>
                                        <td>{{ number_format((float) $sell->selling_price_to_customer, 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('sells.show', $sell) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No sales history for this vehicle.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
