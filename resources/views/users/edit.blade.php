@extends('layouts.admin')

@section('title', 'Edit User | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit User')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users.partials.form', ['submitLabel' => 'Update User'])
    </form>
@endsection
