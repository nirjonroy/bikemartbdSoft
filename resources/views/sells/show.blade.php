@extends('layouts.admin')

@section('title', 'Sale Details | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Sale Details')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('sells.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Vehicle Information</h3>
                    @if ($sell->vehicle)
                        <a href="{{ route('vehicles.show', $sell->vehicle) }}" class="btn btn-sm btn-outline-primary">View Vehicle</a>
                    @endif
                </div>
                <div class="card-body">
                    @if ($sell->vehicle)
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Vehicle</dt>
                            <dd class="col-sm-7">{{ $sell->vehicle->display_name }}</dd>

                            <dt class="col-sm-5">Brand</dt>
                            <dd class="col-sm-7">{{ $sell->vehicle->brand?->name ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Category</dt>
                            <dd class="col-sm-7">{{ $sell->vehicle->category?->name ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Registration</dt>
                            <dd class="col-sm-7">{{ $sell->vehicle->registration_number ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Engine</dt>
                            <dd class="col-sm-7">{{ $sell->vehicle->engine_number ?: 'N/A' }}</dd>
                        </dl>
                    @else
                        <div class="text-muted">No vehicle linked to this sale.</div>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Customer Information</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Name</dt>
                        <dd class="col-sm-7">{{ $sell->name }}</dd>

                        <dt class="col-sm-5">Father's Name</dt>
                        <dd class="col-sm-7">{{ $sell->father_name ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Mobile</dt>
                        <dd class="col-sm-7">{{ $sell->mobile_number ?: 'N/A' }}</dd>

                        @if ($businessSetting->show_quantity_fields ?? true)
                            <dt class="col-sm-5">Quantity</dt>
                            <dd class="col-sm-7">{{ $sell->quantity }}</dd>
                        @endif

                        <dt class="col-sm-5">Address</dt>
                        <dd class="col-sm-7">{{ $sell->address ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Selling Date</dt>
                        <dd class="col-sm-7">{{ $sell->selling_date?->format('d M Y') }}</dd>

                        <dt class="col-sm-5">Selling Price</dt>
                        <dd class="col-sm-7">{{ number_format((float) $sell->selling_price_to_customer, 2) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Payment Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7"><span class="badge {{ $sell->payment_status_badge_class }}">{{ $sell->payment_status_label }}</span></dd>

                        <dt class="col-sm-5">Method</dt>
                        <dd class="col-sm-7">{{ $sell->payment_method_label }}</dd>

                        <dt class="col-sm-5">Information</dt>
                        <dd class="col-sm-7">{{ $sell->payment_information ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Additional Note</h3>
                </div>
                <div class="card-body">
                    {{ $sell->extra_additional_note ?: 'No additional note added.' }}
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-warning">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Picture and Documents</h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('sells.invoice', $sell) }}" target="_blank" class="btn btn-sm btn-dark">Print Invoice</a>
                        <a href="{{ route('sells.edit', $sell) }}" class="btn btn-sm btn-primary">Edit Sale</a>
                    </div>
                </div>
                <div class="card-body">
                    @php $picture = $sell->documentFor('picture'); @endphp

                    @if ($picture)
                        <div class="mb-4">
                            <div class="text-muted small mb-2">Picture</div>
                            <a href="{{ $picture->url }}" target="_blank">
                                <img src="{{ $picture->url }}" alt="Sell picture" class="img-fluid rounded shadow-sm" style="max-height: 280px; object-fit: cover;">
                            </a>
                        </div>
                    @endif

                    <div class="row g-3">
                        @foreach (collect($documentTypes)->except(['picture']) as $type => $label)
                            @php $document = $sell->documentFor($type); @endphp
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-2">{{ $label }}</div>
                                    @if ($document)
                                        <a href="{{ $document->url }}" target="_blank" class="fw-semibold">
                                            {{ $document->original_name ?: basename($document->file_path) }}
                                        </a>
                                    @else
                                        <div class="text-muted">Not uploaded</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
