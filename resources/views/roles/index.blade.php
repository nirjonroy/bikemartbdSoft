@extends('layouts.admin')

@section('title', 'Roles | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Roles')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">System Roles</h3>
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Role
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="fw-semibold">{{ $role->name }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">
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
                                    <div class="text-muted mb-3">No roles found.</div>
                                    <a href="{{ route('roles.create') }}" class="btn btn-primary">Create the first role</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($roles->hasPages())
            <div class="card-footer clearfix">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection
