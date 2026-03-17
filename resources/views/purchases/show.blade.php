@extends('layouts.admin')

@section('title', 'Purchase Details | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Purchase Details')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Vehicle Information</h3>
                    @if ($purchase->vehicle)
                        <a href="{{ route('vehicles.show', $purchase->vehicle) }}" class="btn btn-sm btn-outline-primary">View Vehicle</a>
                    @endif
                </div>
                <div class="card-body">
                    @if ($purchase->vehicle)
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Vehicle</dt>
                            <dd class="col-sm-7">{{ $purchase->vehicle->display_name }}</dd>

                            <dt class="col-sm-5">Brand</dt>
                            <dd class="col-sm-7">{{ $purchase->vehicle->brand?->name ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Category</dt>
                            <dd class="col-sm-7">{{ $purchase->vehicle->category?->name ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Registration</dt>
                            <dd class="col-sm-7">{{ $purchase->vehicle->registration_number ?: 'N/A' }}</dd>

                            <dt class="col-sm-5">Engine</dt>
                            <dd class="col-sm-7">{{ $purchase->vehicle->engine_number ?: 'N/A' }}</dd>
                        </dl>
                    @else
                        <div class="text-muted">No vehicle linked to this purchase.</div>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Owner Information</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Name</dt>
                        <dd class="col-sm-7">{{ $purchase->name }}</dd>

                        <dt class="col-sm-5">Father's Name</dt>
                        <dd class="col-sm-7">{{ $purchase->father_name ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Mobile</dt>
                        <dd class="col-sm-7">{{ $purchase->mobile_number ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Quantity</dt>
                        <dd class="col-sm-7">{{ $purchase->quantity }}</dd>

                        <dt class="col-sm-5">Address</dt>
                        <dd class="col-sm-7">{{ $purchase->address ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Purchase Date</dt>
                        <dd class="col-sm-7">{{ $purchase->purchasing_date?->format('d M Y') }}</dd>

                        <dt class="col-sm-5">Buying Price</dt>
                        <dd class="col-sm-7">{{ number_format((float) $purchase->buying_price_from_owner, 2) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Cost Summary</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Quantity</span>
                        <strong>{{ $purchase->quantity }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Buying price</span>
                        <strong>{{ number_format((float) $purchase->buying_price_from_owner, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Modifying costs</span>
                        <strong>{{ number_format($purchase->total_modifying_cost, 2) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Grand total</span>
                        <strong>{{ number_format($purchase->grand_total, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-warning">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Documents and Pictures</h3>
                    <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-primary">Edit Purchase</a>
                </div>
                <div class="card-body">
                    @if ($purchase->pictureDocuments->isNotEmpty())
                        <div class="row g-3 mb-4">
                            @foreach ($purchase->pictureDocuments as $picture)
                                <div class="col-md-4">
                                    <a href="{{ $picture->url }}" target="_blank">
                                        <img src="{{ $picture->url }}" alt="Purchase picture" class="img-fluid rounded shadow-sm" style="height: 180px; width: 100%; object-fit: cover;">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="row g-3">
                        @foreach ($singleDocumentTypes as $type => $label)
                            @php $document = $purchase->documentFor($type); @endphp
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

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Modifying Costs</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th class="text-end">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchase->modifyingCosts as $cost)
                                    <tr>
                                        <td>{{ $cost->reason }}</td>
                                        <td class="text-end">{{ number_format((float) $cost->cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No modifying cost added.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-light">
                <div class="card-header">
                    <h3 class="card-title">Additional Note</h3>
                </div>
                <div class="card-body">
                    {{ $purchase->extra_additional_note ?: 'No additional note added.' }}
                </div>
            </div>
        </div>
    </div>
@endsection
