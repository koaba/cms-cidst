@foreach(\App\Services\SeoService::structuredData($model ?? null, $seoPage ?? null) as $seoBlock)
<script type="application/ld+json">
{!! \App\Services\SeoService::jsonLd($seoBlock) !!}
</script>
@endforeach
