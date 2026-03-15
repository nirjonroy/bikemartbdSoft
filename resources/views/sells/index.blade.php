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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mobile</th>
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
                                    <div class="fw-semibold">{{ $sell->name }}</div>
                                    <div class="small text-muted">{{ $sell->father_name ?: 'Father name not added' }}</div>
                                </td>
                                <td>{{ $sell->mobile_number ?: 'N/A' }}</td>
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
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3">No sale records found.</div>
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
