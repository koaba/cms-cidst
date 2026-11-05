@php
    $seoPageMeta = ! $model && $seoPage ? \App\Services\SeoService::forPage($seoPage, $seoReplace) : null;
    $seoTitle = $model ? \App\Services\SeoService::title($model) : ($seoPageMeta['title'] ?? config('app.name', 'CIDST'));
    $seoDescription = $model ? \App\Services\SeoService::description($model) : ($seoPageMeta['description'] ?? null);
    $seoImage = $model ? \App\Services\SeoService::image($model) : null;
    $seoSocialImage = $seoImage ?? \App\Services\SeoService::logoUrl();
    $seoCanonical = $model ? \App\Services\SeoService::canonical($model) : \App\Services\SeoService::listCanonical();
    $seoNoIndex = $model ? \App\Services\SeoService::noIndex($model) : ($seoPageMeta['noindex'] ?? false);
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
<meta property="og:locale" content="fr_FR">
@if($seoSocialImage)
    <meta property="og:image" content="{{ $seoSocialImage }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
@if($seoDescription)
    <meta name="twitter:description" content="{{ $seoDescription }}">
@endif
@if($seoSocialImage)
    <meta name="twitter:image" content="{{ $seoSocialImage }}">
@endif

@include('partials.seo-jsonld', ['model' => $model ?? null, 'seoPage' => $seoPage ?? null])