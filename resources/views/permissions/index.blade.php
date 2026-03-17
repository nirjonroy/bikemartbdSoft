@extends('layouts.admin')

@section('title', 'Permissions | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Permissions')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Permissions</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">System Permissions</h3>
            <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Permission
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Guard</th>
                            <th>Roles</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $permission)
                            <tr>
                                <td class="fw-semibold">{{ $permission->name }}</td>
                                <td>{{ $permission->guard_name }}</td>
                                <td>{{ $permission->roles_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('permissions.destroy', $permission) }}" onsubmit="return confirm('Delete this permission?');">
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
                                    <div class="text-muted mb-3">No permissions found.</div>
                                    <a href="{{ route('permissions.create') }}" class="btn btn-primary">Create the first permission</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($permissions->hasPages())
            <div class="card-footer clearfix">
                {{ $permissions->links() }}
            </div>
        @endif
    </div>
@endsection
