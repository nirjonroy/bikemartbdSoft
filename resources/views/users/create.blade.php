@extends('layouts.admin')

@section('title', 'New User | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create User')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users.partials.form', ['submitLabel' => 'Create User'])
    </form>
@endsection
