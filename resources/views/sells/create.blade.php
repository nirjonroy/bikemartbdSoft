@extends('layouts.admin')

@section('title', 'New Sale | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Sale')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('sells.index') }}">Sales</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('sells.store') }}" enctype="multipart/form-data">
        @csrf
        @include('sells.partials.form', ['submitLabel' => 'Create Sale'])
    </form>
@endsection
