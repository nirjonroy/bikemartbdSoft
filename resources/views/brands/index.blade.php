@extends('layouts.admin')

@section('title', 'Brands | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Brands')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Brands</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Vehicle Brands</h3>
            <a href="{{ route('brands.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Brand
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Vehicles</th>
                            <th>Notes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                            <tr>
                                <td class="fw-semibold">{{ $brand->name }}</td>
                                <td>{{ $brand->vehicles_count }}</td>
                                <td>{{ $brand->notes ?: 'No notes added.' }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted mb-3">No brands found.</div>
                                    <a href="{{ route('brands.create') }}" class="btn btn-primary">Create the first brand</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($brands->hasPages())
            <div class="card-footer clearfix">
                {{ $brands->links() }}
            </div>
        @endif
    </div>
@endsection
