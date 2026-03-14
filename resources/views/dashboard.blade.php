@extends('layouts.admin')

@section('title', 'Dashboard | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $staffCount }}</h3>
                    <p>Staff Accounts</p>
                </div>
                <i class="small-box-icon bi bi-people-fill"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $profileCompletion }}<sup class="fs-5">%</sup></h3>
                    <p>Business Profile Complete</p>
                </div>
                <i class="small-box-icon bi bi-building-check"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $businessSetting->currency_code ?: 'BDT' }}</h3>
                    <p>Default Currency</p>
                </div>
                <i class="small-box-icon bi bi-cash-coin"></i>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>{{ $businessSetting->timezone ?: config('app.timezone') }}</h3>
                    <p>Timezone</p>
                </div>
                <i class="small-box-icon bi bi-clock-history"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Business Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-4 mb-md-0">
                            @if ($businessSetting->logo_path)
                                <img
                                    src="{{ $businessSetting->logo_url }}"
                                    alt="{{ $businessSetting->display_name }}"
                                    class="settings-logo-preview rounded-circle shadow"
                                >
                            @else
                                <div class="settings-logo-fallback shadow mx-auto">{{ $businessSetting->initials }}</div>
                            @endif
                        </div>

                        <div class="col-md-9">
                            <h3 class="mb-1">{{ $businessSetting->display_name }}</h3>
                            <p class="text-muted mb-4">
                                This dashboard now uses the AdminLTE assets from your `dashboard-design` folder
                                and pulls live business identity details from the database.
                            </p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small mb-1">Email</div>
                                        <div class="fw-semibold">{{ $businessSetting->email ?: 'Add a business email' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small mb-1">Phone</div>
                                        <div class="fw-semibold">{{ $businessSetting->phone ?: 'Add a contact number' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small mb-1">Website</div>
                                        <div class="fw-semibold">{{ $businessSetting->website ?: 'Add your website' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small mb-1">Address</div>
                                        <div class="fw-semibold">{{ $businessSetting->address ?: 'Add your business address' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('business-settings.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>
                        Update Business Information
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('business-settings.edit') }}" class="btn btn-outline-primary">
                            <i class="bi bi-building-gear me-1"></i>
                            Business Information
                        </a>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-person-circle me-1"></i>
                            Profile Settings
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Invoice Footer</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        {{ $businessSetting->invoice_footer ?: 'Add a footer note for invoices and receipts from Business Information.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
