@extends('layouts.admin')

@section('title', 'Edit Permission | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Permission')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('permissions.update', $permission) }}">
        @csrf
        @method('PUT')
        @include('permissions.partials.form', ['submitLabel' => 'Update Permission'])
    </form>
@endsection
