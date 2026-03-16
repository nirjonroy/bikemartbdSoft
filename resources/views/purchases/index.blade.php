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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Name</th>
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
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-3">No purchase records found.</div>
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
