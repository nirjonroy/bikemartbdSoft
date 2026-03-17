@extends('layouts.admin')

@section('title', 'Profile | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Profile')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Profile</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-body text-center">
                    <img
                        src="{{ asset('adminlte/assets/img/avatar.png') }}"
                        alt="{{ $user->name }}"
                        class="rounded-circle shadow mb-3"
                        style="width: 96px; height: 96px; object-fit: cover;"
                    >
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <div class="text-muted mb-3">{{ $user->email }}</div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                        @forelse ($user->roles as $role)
                            <span class="badge text-bg-primary">{{ $role->name }}</span>
                        @empty
                            <span class="badge text-bg-secondary">No role assigned</span>
                        @endforelse
                    </div>

                    <div class="small text-muted">
                        Member since {{ $user->created_at?->format('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Account Overview</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Email verification</span>
                        <strong>{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total roles</span>
                        <strong>{{ $user->roles->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Account ID</span>
                        <strong>#{{ $user->id }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
