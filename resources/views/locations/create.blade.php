@extends('layouts.admin')

@section('title', 'New Location | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Location')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('locations.store') }}">
        @csrf
        @include('locations.partials.form', ['submitLabel' => 'Create Location'])
    </form>
@endsection
