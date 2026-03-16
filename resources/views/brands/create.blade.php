@extends('layouts.admin')

@section('title', 'New Brand | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Brand')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('brands.index') }}">Brands</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('brands.store') }}">
        @csrf
        @include('brands.partials.form', ['submitLabel' => 'Create Brand'])
    </form>
@endsection
