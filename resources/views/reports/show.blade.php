@extends('layouts.admin')

@section('title', $reportTitle . ' | ' . config('app.name', 'BikeMart POS'))
@section('page_title', $reportTitle)

@section('breadcrumbs')
    <li class="breadcrumb-item">Reports</li>
    <li class="breadcrumb-item active">{{ $reportTitle }}</li>
@endsection

@section('content')
    <div class="alert alert-info">
        <div class="fw-semibold">{{ $reportDescription }}</div>
        <div class="small mt-1">
            Active location: <strong>{{ $activeLocation->display_name }}</strong>
        </div>
    </div>

    @if ($reportNote)
        <div class="alert alert-warning">
            {{ $reportNote }}
        </div>
    @endif

    @if ($showsDateFilters)
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Report</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route($reportRouteName) }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <a href="{{ route($reportRouteName) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($summaryCards)
        <div class="row">
            @foreach ($summaryCards as $summaryCard)
                <div class="col-xl-3 col-md-6">
                    <div class="small-box text-bg-{{ $summaryCard['color'] }}">
                        <div class="inner">
                            <h3>{{ $summaryCard['value'] }}</h3>
                            <p>{{ $summaryCard['label'] }}</p>
                            <div class="small">{{ $summaryCard['hint'] }}</div>
                        </div>
                        <i class="small-box-icon {{ $summaryCard['icon'] }}"></i>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @foreach ($sections as $section)
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $section['title'] }}</h3>
            </div>
            @if (! empty($section['description']))
                <div class="card-body border-bottom bg-light">
                    <div class="text-muted small">{{ $section['description'] }}</div>
                </div>
            @endif
            <div class="card-body p-0">
                @if (! empty($section['rows']))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    @foreach ($section['columns'] as $column)
                                        <th>{{ $column['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['rows'] as $row)
                                    <tr>
                                        @foreach ($section['columns'] as $column)
                                            @php
                                                $cell = $row[$column['key']] ?? null;
                                                $cellType = is_array($cell) ? ($cell['type'] ?? 'text') : 'text';
                                            @endphp
                                            <td>
                                                @if ($cellType === 'badge')
                                                    <span class="badge {{ $cell['class'] ?? 'text-bg-secondary' }}">{{ $cell['value'] }}</span>
                                                @elseif ($cellType === 'html')
                                                    {!! $cell['value'] !!}
                                                @else
                                                    {{ is_array($cell) ? ($cell['value'] ?? '') : $cell }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        {{ $section['empty'] ?? 'No data available.' }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endsection
