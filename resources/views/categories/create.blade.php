@extends('layouts.admin')

@section('title', 'New Category | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Category')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('categories.store') }}">
        @csrf
        @include('categories.partials.form', ['submitLabel' => 'Create Category'])
    </form>
@endsection
