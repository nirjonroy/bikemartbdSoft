@extends('layouts.admin')

@section('title', 'Locations | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Locations')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Locations</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Business Locations</h3>
            <a href="{{ route('locations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Location
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Users</th>
                            <th>Purchases</th>
                            <th>Sales</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $location->display_name }}</div>
                                    <div class="small text-muted">{{ $location->phone ?: 'No phone' }} {{ $location->email ? '| ' . $location->email : '' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $location->status_badge_class }}">{{ $location->status_label }}</span>
                                    @if ($activeLocation && $activeLocation->id === $location->id)
                                        <div class="small text-muted mt-1">Current location</div>
                                    @endif
                                </td>
                                <td>{{ $location->users_count }}</td>
                                <td>{{ $location->purchases_count }}</td>
                                <td>{{ $location->sells_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('locations.edit', $location) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?');">
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
                                    <div class="text-muted mb-3">No locations found.</div>
                                    <a href="{{ route('locations.create') }}" class="btn btn-primary">Create the first location</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($locations->hasPages())
            <div class="card-footer clearfix">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
@endsection
