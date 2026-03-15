@extends('layouts.admin')

@section('title', 'Edit Sale | ' . config('app.name', 'BikeMart POS'))
@section('page_title', 'Edit Sale')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('sells.index') }}">Sales</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sells.show', $sell) }}">Details</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('sells.update', $sell) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('sells.partials.form', ['submitLabel' => 'Update Sale'])
    </form>
@endsection
