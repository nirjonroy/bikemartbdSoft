@extends('layouts.admin')

@section('title', 'Edit Brand | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Brand')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('brands.index') }}">Brands</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('brands.update', $brand) }}">
        @csrf
        @method('PUT')
        @include('brands.partials.form', ['submitLabel' => 'Update Brand'])
    </form>
@endsection
