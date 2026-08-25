@php
    $seoTitle = $model ? \App\Services\SeoService::title($model) : ($title ?? config('app.name', 'CIDST'));
    $seoDescription = $model ? \App\Services\SeoService::description($model) : null;
    $seoImage = $model ? \App\Services\SeoService::image($model) : null;
    $seoCanonical = $model ? \App\Services\SeoService::canonical($model) : url()->current();
    $seoNoIndex = $model ? \App\Services\SeoService::noIndex($model) : false;
    $seoOgType = $model ? \App\Services\SeoService::ogType($model) : 'website';
@endphp

<title>{{ $seoTitle }}</title>
<link rel="canonical" href="{{ $seoCanonical }}">

@if($seoDescription)
    <meta name="description" content="{{ $seoDescription }}">
@endif

@if($seoNoIndex)
    <meta name="robots" content="noindex, nofollow">
@endif

{{-- Open Graph --}}
<meta property="og:title" content="{{ $seoTitle }}">
@if($seoDescription)
    <meta property="og:description" content="{{ $seoDescription }}">
@endif
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:type" content="{{ $seoOgType }}">
<meta property="og:site_name" content="{{ config('app.name', 'CIDST') }}">
@if($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
@if($seoDescription)
    <meta name="twitter:description" content="{{ $seoDescription }}">
@endif
@if($seoImage)
    <meta name="twitter:image" content="{{ $seoImage }}">
@endif

@include('partials.seo-jsonld', ['model' => $model ?? null])