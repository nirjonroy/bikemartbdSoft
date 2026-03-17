@extends('layouts.admin')

@section('title', 'Edit Role | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Role')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('roles.partials.form', ['submitLabel' => 'Update Role'])
    </form>
@endsection
