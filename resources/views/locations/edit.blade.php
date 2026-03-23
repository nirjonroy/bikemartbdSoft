@extends('layouts.admin')

@section('title', 'Edit Location | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Location')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('locations.update', $location) }}">
        @csrf
        @method('PUT')
        @include('locations.partials.form', ['submitLabel' => 'Update Location'])
    </form>
@endsection
