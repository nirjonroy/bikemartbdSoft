@extends('layouts.admin')

@section('title', 'Edit Category | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Category')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('categories.update', $category) }}">
        @csrf
        @method('PUT')
        @include('categories.partials.form', ['submitLabel' => 'Update Category'])
    </form>
@endsection
