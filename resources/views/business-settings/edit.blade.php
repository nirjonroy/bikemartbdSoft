@extends('layouts.admin')

@section('title', 'Business Information | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Business Information')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Business Information</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Brand Preview</h3>
                </div>
                <div class="card-body text-center">
                    @if ($businessSetting->logo_path)
                        <img
                            src="{{ $businessSetting->logo_url }}"
                            alt="{{ $businessSetting->display_name }}"
                            class="settings-logo-preview rounded-circle shadow mb-3"
                        >
                    @else
                        <div class="settings-logo-fallback shadow mx-auto mb-3">{{ $businessSetting->initials }}</div>
                    @endif

                    <h4 class="mb-1">{{ $businessSetting->display_name }}</h4>
                    <p class="text-muted mb-3">{{ $businessSetting->email ?: 'Primary email not added' }}</p>

                    <div class="list-group list-group-flush text-start">
                        <div class="list-group-item">
                            <span class="fw-semibold">Phone:</span>
                            <span class="float-end">{{ $businessSetting->phone ?: 'Not set' }}</span>
                        </div>
                        <div class="list-group-item">
                            <span class="fw-semibold">Website:</span>
                            <span class="float-end">{{ $businessSetting->website ?: 'Not set' }}</span>
                        </div>
                        <div class="list-group-item">
                            <span class="fw-semibold">Currency:</span>
                            <span class="float-end">{{ $businessSetting->currency_code ?: 'BDT' }}</span>
                        </div>
                        <div class="list-group-item">
                            <span class="fw-semibold">Timezone:</span>
                            <span class="float-end">{{ $businessSetting->timezone ?: config('app.timezone') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Update Business Details</h3>
                </div>
                <form method="POST" action="{{ route('business-settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="business_name" class="form-label">Business Name</label>
                                <input
                                    type="text"
                                    id="business_name"
                                    name="business_name"
                                    class="form-control @error('business_name') is-invalid @enderror"
                                    value="{{ old('business_name', $businessSetting->business_name) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Business Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $businessSetting->email) }}"
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $businessSetting->phone) }}"
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input
                                    type="url"
                                    id="website"
                                    name="website"
                                    class="form-control @error('website') is-invalid @enderror"
                                    value="{{ old('website', $businessSetting->website) }}"
                                    placeholder="https://example.com"
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="currency_code" class="form-label">Currency Code</label>
                                <input
                                    type="text"
                                    id="currency_code"
                                    name="currency_code"
                                    class="form-control @error('currency_code') is-invalid @enderror"
                                    value="{{ old('currency_code', $businessSetting->currency_code ?: 'BDT') }}"
                                    maxlength="10"
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="timezone" class="form-label">Timezone</label>
                                <input
                                    type="text"
                                    id="timezone"
                                    name="timezone"
                                    class="form-control @error('timezone') is-invalid @enderror"
                                    value="{{ old('timezone', $businessSetting->timezone ?: config('app.timezone')) }}"
                                    placeholder="Asia/Dhaka"
                                >
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea
                                    id="address"
                                    name="address"
                                    rows="3"
                                    class="form-control @error('address') is-invalid @enderror"
                                >{{ old('address', $businessSetting->address) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label for="invoice_footer" class="form-label">Invoice Footer Note</label>
                                <textarea
                                    id="invoice_footer"
                                    name="invoice_footer"
                                    rows="3"
                                    class="form-control @error('invoice_footer') is-invalid @enderror"
                                >{{ old('invoice_footer', $businessSetting->invoice_footer) }}</textarea>
                            </div>

                            <div class="col-md-8">
                                <label for="logo" class="form-label">Business Logo</label>
                                <input
                                    type="file"
                                    id="logo"
                                    name="logo"
                                    class="form-control @error('logo') is-invalid @enderror"
                                    accept="image/*"
                                >
                                <div class="form-text">PNG, JPG, or WEBP up to 2MB.</div>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="remove_logo"
                                        name="remove_logo"
                                    >
                                    <label class="form-check-label" for="remove_logo">
                                        Remove current logo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Changes apply immediately to the dashboard branding.</span>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-floppy me-1"></i>
                            Save Business Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
