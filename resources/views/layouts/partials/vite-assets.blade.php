@php
    $hotFile = public_path('hot');
    $manifestPath = public_path('build/manifest.json');
@endphp

@if (file_exists($hotFile))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@elseif (file_exists($manifestPath))
    @php
        $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
        $cssEntry = $manifest['resources/css/app.css']['file'] ?? null;
        $jsEntry = $manifest['resources/js/app.js']['file'] ?? null;
        $baseUrl = rtrim(request()->getBaseUrl(), '/');
        $buildAsset = fn (string $path) => ($baseUrl !== '' ? $baseUrl : '').'/build/'.$path;
    @endphp

    @if ($cssEntry)
        <link rel="preload" as="style" href="{{ $buildAsset($cssEntry) }}">
        <link rel="stylesheet" href="{{ $buildAsset($cssEntry) }}">
    @endif

    @if ($jsEntry)
        <link rel="modulepreload" href="{{ $buildAsset($jsEntry) }}">
        <script type="module" src="{{ $buildAsset($jsEntry) }}"></script>
    @endif
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
