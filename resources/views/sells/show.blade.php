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

                        <dt class="col-sm-5">Address</dt>
                        <dd class="col-sm-7">{{ $sell->address ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Selling Date</dt>
                        <dd class="col-sm-7">{{ $sell->selling_date?->format('d M Y') }}</dd>

                        <dt class="col-sm-5">Selling Price</dt>
                        <dd class="col-sm-7">{{ number_format((float) $sell->selling_price_to_customer, 2) }}</dd>
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
                    <a href="{{ route('sells.edit', $sell) }}" class="btn btn-sm btn-primary">Edit Sale</a>
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
