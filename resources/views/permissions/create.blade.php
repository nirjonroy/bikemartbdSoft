@extends('layouts.admin')

@section('title', 'New Permission | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Permission')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('permissions.store') }}">
        @csrf
        @include('permissions.partials.form', ['submitLabel' => 'Create Permission'])
    </form>
@endsection
