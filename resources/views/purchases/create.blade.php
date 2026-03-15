@extends('layouts.admin')

@section('title', 'New Purchase | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Create Purchase')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('purchases.store') }}" enctype="multipart/form-data">
        @csrf
        @include('purchases.partials.form', ['submitLabel' => 'Create Purchase'])
    </form>
@endsection
