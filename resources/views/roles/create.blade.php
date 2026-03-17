@extends('layouts.admin')

@section('title', 'New Role | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Role')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles.partials.form', ['submitLabel' => 'Create Role'])
    </form>
@endsection
