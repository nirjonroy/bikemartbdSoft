@extends('layouts.admin')

@section('title', 'Edit Vehicle | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Vehicle')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicles.show', $vehicle) }}">Details</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
        @csrf
        @method('PUT')
        @include('vehicles.partials.form', ['submitLabel' => 'Update Vehicle'])
    </form>
@endsection
