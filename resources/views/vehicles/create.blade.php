@extends('layouts.admin')

@section('title', 'New Vehicle | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Vehicle')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicles</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('vehicles.store') }}">
        @csrf
        @include('vehicles.partials.form', ['submitLabel' => 'Create Vehicle'])
    </form>
@endsection
