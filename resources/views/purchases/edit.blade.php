@extends('layouts.admin')

@section('title', 'Edit Purchase | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Purchase')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.show', $purchase) }}">Details</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('purchases.update', $purchase) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('purchases.partials.form', ['submitLabel' => 'Update Purchase'])
    </form>
@endsection
